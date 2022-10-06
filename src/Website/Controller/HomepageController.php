<?php


namespace Olympus\Website\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as Twig;

#[AsController]
class HomepageController
{
    #[Route(
        path: '/',
        name: 'olympus_home',
        methods: ['GET']
    )]
    public function dashboard(Twig $twig): Response
    {
        $content = $twig->render('@OlympusWebsite/dashboard.html.twig', []);
        return new Response($content, Response::HTTP_OK);
    }
}