<?php

namespace App;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RedirectionIoClient
{
    private const string API_URL = 'https://api.redirection.io/';

    public function __construct(
        private readonly HttpClientInterface $client,

        #[Autowire('%env(REDIRECTION_IO_PROJECT_ID)%')]
        private readonly string $projectId,

        #[Autowire('%env(REDIRECTION_IO_API_KEY)%')]
        private readonly string $apiKey,
    ) {
    }

    public function shortenUrl(string $url, ?string $shortCode = null, ?string $description = null): string
    {
        $draft = $this->createDraft($url, $shortCode, $description);

        if (!isset($draft['rule']['trigger']['source'])) {
            throw new \RuntimeException('Failed to call the API.');
        }

        $this->publish();

        return trim($draft['rule']['trigger']['source'], '/');
    }

    public function shortCodeExists(string $shortCode): bool
    {
        return \count($this->getDrafts($shortCode)) > 0 || \count($this->getPublishedRules($shortCode)) > 0;
    }

    /**
     * @return array<string, string>
     */
    private function getDefaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createDraft(string $url, ?string $shortCode, ?string $description): array
    {
        if (null === $shortCode) {
            $shortCode = $this->getRandomShortCode();
        }

        $payload = [
            'project' => '/projects/' . $this->projectId,
            'source' => '/' . $shortCode,
            'target' => $url,
            'statusCode' => 302,
        ];

        if (null !== $description) {
            $payload['description'] = $description;
        }

        $draft = $this->client->request('POST', $this->getApiUrl('redirections'), [
            'headers' => $this->getDefaultHeaders(),
            'json' => $payload,
        ]);

        return $draft->toArray();
    }

    private function publish(): void
    {
        $this->client->request('POST', $this->getApiUrl(\sprintf('projects/%s/publish', $this->projectId)), [
            'headers' => $this->getDefaultHeaders(),
            'json' => [
                'projectId' => $this->projectId,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDrafts(string $shortCode): array
    {
        $drafts = $this->client->request('GET', $this->getApiUrl('drafts?projectId=' . $this->projectId), [
            'headers' => $this->getDefaultHeaders(),
            'query' => [
                'project_id' => $this->projectId,
                'triggerUrl' => '/' . $shortCode,
            ],
        ]);

        return $drafts->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getPublishedRules(string $shortCode): array
    {
        $publishedRules = $this->client->request('GET', $this->getApiUrl('rules?projectId=' . $this->projectId), [
            'headers' => $this->getDefaultHeaders(),
            'query' => [
                'project_id' => $this->projectId,
                'triggerUrl' => '/' . $shortCode,
            ],
        ]);

        return $publishedRules->toArray();
    }

    private function getRandomShortCode(): string
    {
        return hash('crc32', uniqid());
    }

    private function getApiUrl(string $path): string
    {
        return self::API_URL . $path;
    }
}
