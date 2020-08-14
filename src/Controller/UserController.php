<?php

namespace App\Controller;

use App\Classes\JwtAuth;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validation;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user")
     */
    public function index()
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        $users = $userRepository->findAll();
        $json  = $this->get('serializer')->serialize($users, 'json');

        /*$response = new Response();
        $response->setData($json);
        $response->headers->set('Content-Type', 'application/json');
        return $response;*/

        return JsonResponse::fromJsonString($json);
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     *
     * @return JsonResponse
     */
    public function create(Request $request, EntityManagerInterface $em)
    {
        $params = json_decode($request->get('json', null));

        if (!isset($params)) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "Params cannot be null"
            ], Response::HTTP_BAD_REQUEST);
        }

        $name     = !empty($params->name) ? $params->name : null;
        $surname  = !empty($params->surname) ? $params->surname : null;
        $email    = !empty($params->email) ? $params->email : null;
        $password = !empty($params->password) ? $params->password : null;

        $validator         = Validation::createValidator();
        $validate_name     = $validator->validate($name, [new NotBlank(), new NotNull()]);
        $validate_surname  = $validator->validate($surname, [new NotBlank(), new NotNull()]);
        $validate_email    = $validator->validate($email, [new Email()]);
        $validate_password = $validator->validate($password, [new NotBlank(), new NotNull()]);

        if (count($validate_name) > 0 || count($validate_surname) > 0 || count($validate_email) > 0 || count($validate_password) > 0) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "Params error"
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setName($name);
        $user->setSurname($surname);
        $user->setEmail($email);
        $user->setRole('ROLE_USER');
        $user->setCreatedAt(new DateTime());
        $user->setPassword(hash("sha256", $password));

        $issetUser = $em->getRepository(User::class)->findBy(["email" => $email]);
        if ($issetUser) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "User already exist"
            ], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            "status"  => "success",
            "message" => "User successfully registered"
        ], Response::HTTP_OK);
    }

    /**
     *
     * @Route("/login", name="login", methods={"POST"})
     *
     * @param Request $request
     * @param JwtAuth $jwtAuth
     *
     * @return JsonResponse
     */
    public function login(Request $request, JwtAuth $jwtAuth)
    {
        $params = json_decode($request->get('json', null));

        if (!isset($params)) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "Params cannot be null"
            ], Response::HTTP_BAD_REQUEST);
        }

        $email    = !empty($params->email) ? $params->email : null;
        $password = !empty($params->password) ? $params->password : null;
        $getToken = !empty($params->getToken) ? $params->getToken : null;

        $validator         = Validation::createValidator();
        $validate_email    = $validator->validate($email, [new Email()]);
        $validate_password = $validator->validate($password, [new NotBlank(), new NotNull()]);

        if (count($validate_email) > 0 || count($validate_password) > 0) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "Params error"
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $jwtAuth->signUp($email, $password, $getToken);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            "status"  => "success",
            "message" => "User successfully logged",
            "data" => $user
        ], Response::HTTP_OK);
    }
}
