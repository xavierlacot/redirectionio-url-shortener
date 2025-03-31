<?php

namespace App\Controller;

use App\Form\ShortUrlType;
use App\RedirectionIoClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        $form = $this->createForm(ShortUrlType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $shortUrl = $this->redirectionIoClient->shortenUrl($data['url'], $data['shortCode']);

                return $this->redirectToRoute('app_url_shortener', [
                    'shortUrl' => $shortUrl,
                ]);
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'An error occurred while creating the short URL, please try again. The error was: ' . $e->getMessage()
                );
            }
        }

        return $this->render('url_shortener/index.html.twig', [
            'form' => $form->createView(),
            'short_url' => $shortUrl,
            'short_url_domain' => $this->shortUrlDomain,
        ]);
    }
}
