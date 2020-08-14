<?php

namespace App\Controller;

use App\Classes\JwtAuth;
use App\Entity\User;
use App\Entity\Video;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validation;

class VideoController extends AbstractController
{
    /**
     * @Route("/video", name="video", methods={"GET"})
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path'    => 'src/Controller/VideoController.php',
        ]);
    }

    /**
     * @Route("/video", name="new_video", methods={"POST"})
     *
     * @param Request $request
     * @param JwtAuth $jwtAuth
     * @param EntityManagerInterface $em
     *
     * @return JsonResponse
     */
    public function newVideo(Request $request, JwtAuth $jwtAuth, EntityManagerInterface $em)
    {
        $jwt = $request->headers->get('Authorization', null);

        if (!$jwtAuth->checkToken($jwt)) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "Authentication error"
            ], Response::HTTP_FORBIDDEN);
        }

        $params   = json_decode($request->get('json', null));
        $identity = $jwtAuth->checkToken($jwt, true);

        $title       = !empty($params->title) ? $params->title : null;
        $description = !empty($params->description) ? $params->description : null;
        $url         = !empty($params->url) ? $params->url : null;

        $validator            = Validation::createValidator();
        $validate_title       = $validator->validate($title, [new NotBlank(), new NotNull()]);
        $validate_url         = $validator->validate($url, [new NotBlank(), new NotNull()]);

        if (count($validate_title) > 0 || count($validate_url) > 0) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "Params error"
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->find($identity->sub);

        $video = new Video();
        $video->setUser($user);
        $video->setTitle($title);
        $video->setDescription($description);
        $video->setUrl($url);
        $video->setCreatedAt(new DateTime("now"));

        $em->persist($video);
        $em->flush();

        return new JsonResponse([
            "status"  => "success",
            "message" => "Video successfully saved",
            "video"   => json_decode($this->get('serializer')->serialize($video, 'json'), true)
        ], Response::HTTP_OK);
    }
}
