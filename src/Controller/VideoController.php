<?php

namespace App\Controller;

use App\Classes\JwtAuth;
use App\Entity\User;
use App\Entity\Video;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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

        $validator      = Validation::createValidator();
        $validate_title = $validator->validate($title, [new NotBlank(), new NotNull()]);
        $validate_url   = $validator->validate($url, [new NotBlank(), new NotNull()]);

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

    /**
     * @Route("/videos", name="list_video", methods={"GET"})
     *
     * @param Request $request
     * @param JwtAuth $jwtAuth
     * @param EntityManagerInterface $em
     * @param PaginatorInterface $paginator
     *
     * @return JsonResponse
     */
    public function listVideos(Request $request, JwtAuth $jwtAuth, EntityManagerInterface $em, PaginatorInterface $paginator)
    {
        $jwt = $request->headers->get('Authorization', null);

        if (!$jwtAuth->checkToken($jwt)) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "Authentication error"
            ], Response::HTTP_FORBIDDEN);
        }

        $identity     = $jwtAuth->checkToken($jwt, true);
        $query        = $em->createQuery("SELECT v FROM App\Entity\Video v WHERE v.user = :user ORDER BY v.id DESC")
            ->setParameter("user", $identity->sub);
        $page         = $request->query->getInt('page', 1);
        $itemsPerPage = 5;
        $pagination   = $paginator->paginate($query, $page, $itemsPerPage);
        $total        = $pagination->getTotalItemCount();

        return new JsonResponse([
            "status"       => "success",
            "totalItems"   => $total,
            "page"         => $page,
            "itemsPerPage" => $itemsPerPage,
            "totalPages"   => ceil($total / $itemsPerPage),
            "videos"       => json_decode($this->get('serializer')->serialize($pagination, 'json'), true),
            "user"         => $identity->sub
        ], Response::HTTP_OK);
    }

    /**
     * @Route("/video/{id}", name="get_video", methods={"GET"})
     *
     * @param Request $request
     * @param JwtAuth $jwtAuth
     * @param EntityManagerInterface $em
     * @param $id
     *
     * @return JsonResponse
     */
    public function getVideo(Request $request, JwtAuth $jwtAuth, EntityManagerInterface $em, $id)
    {
        $jwt = $request->headers->get('Authorization', null);

        if (!$jwtAuth->checkToken($jwt)) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "Authentication error"
            ], Response::HTTP_FORBIDDEN);
        }

        $identity = $jwtAuth->checkToken($jwt, true);
        $video    = $em->getRepository(Video::class)->findOneBy(["id" => $id, "user" => $identity->sub]);

        if (!$video) {
            return new JsonResponse([
                "status"  => "error",
                "message" => "Video not found"
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            "status" => "success",
            "video"  => json_decode($this->get('serializer')->serialize($video, 'json'), true),
        ], Response::HTTP_OK);
    }
}
