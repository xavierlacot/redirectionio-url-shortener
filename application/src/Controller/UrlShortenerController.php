<?php

namespace App\Controller;

use App\Form\Model\ShortUrl;
use App\Form\ShortUrlType;
use App\RedirectionIoClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

final class UrlShortenerController extends AbstractController
{
    public function __construct(
        private readonly RedirectionIoClient $redirectionIoClient,

        #[Autowire('%env(REDIRECTION_IO_SHORT_URL_DOMAIN)%')]
        private readonly string $shortUrlDomain,
    ) {
    }

    #[Route('/{shortUrl}', name: 'app_url_shortener')]
    public function index(Request $request, ?string $shortUrl = null): Response
    {
        $shortUrlDto = new ShortUrl();
        $form = $this->createForm(ShortUrlType::class, $shortUrlDto, [
            'action' => $this->generateUrl('app_url_shortener'),
        ]);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // @phpstan-ignore-next-line
                $shortUrl = $this->redirectionIoClient->shortenUrl($shortUrlDto->url, $shortUrlDto->shortCode);

                return $this->redirectToRoute('app_url_shortener', [
                    'shortUrl' => $shortUrl,
                ]);
            }
        } catch (ClientException|ServerException $e) {
            $response = $e->getResponse();
            $body = json_decode((string) $response->getContent(false), true);
            $message = (null !== $body && isset($body['detail'])) ? $body['detail'] : $e->getMessage();
            $form->addError(new FormError($message));
        } catch (\RuntimeException|ExceptionInterface $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('url_shortener/index.html.twig', [
            'form' => $form->createView(),
            'short_url' => $shortUrl,
            'short_url_domain' => $this->shortUrlDomain,
        ]);
    }
}
