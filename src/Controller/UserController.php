<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Video;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user")
     */
    public function index()
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        $users = $userRepository->findAll();
        $json = $this->get('serializer')->serialize($users, 'json');

        /*$response = new Response();
        $response->setData($json);
        $response->headers->set('Content-Type', 'application/json');
        return $response;*/

        return JsonResponse::fromJsonString($json);
    }
}
