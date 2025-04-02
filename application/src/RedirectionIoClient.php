<?php

namespace App;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RedirectionIoClient
{
    public function __construct(
        #[Target('redirectionio.client')]
        private readonly HttpClientInterface $client,

        #[Autowire('%env(REDIRECTION_IO_PROJECT_ID)%')]
        private readonly string $projectId,
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

        $draft = $this->client->request('POST', 'redirections', [
            'json' => $payload,
        ]);

        return $draft->toArray();
    }

    private function publish(): void
    {
        $this->client->request('POST', \sprintf('projects/%s/publish', $this->projectId), [
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
        $drafts = $this->client->request('GET', 'drafts', [
            'query' => [
                'projectId' => $this->projectId,
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
        $publishedRules = $this->client->request('GET', 'rules', [
            'query' => [
                'projectId' => $this->projectId,
                'triggerUrl' => '/' . $shortCode,
            ],
        ]);

        return $publishedRules->toArray();
    }

    private function getRandomShortCode(): string
    {
        return bin2hex(random_bytes(5));
    }
}
