<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BsitController extends AbstractController
{
    #[Route('/bsit', name: 'bsit')]
    public function index(): Response
    {
        return $this->render('bsit/index.html.twig', [
            'controller_name' => 'BsitController',
        ]);
    }
}
