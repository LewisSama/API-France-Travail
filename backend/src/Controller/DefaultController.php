<?php

namespace App\Controller;

use App\Service\FranceTravailAPIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{

    #[Route('/', name: 'home')]
    public function home(FranceTravailAPIService $franceTravailAPIService): Response
    {

        dump($franceTravailAPIService->retrieveAllOffersAndPersist());

        return $this->render('base.html.twig');
    }

}