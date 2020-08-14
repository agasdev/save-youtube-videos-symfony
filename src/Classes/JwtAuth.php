<?php


namespace App\Classes;

use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use App\Entity\User;
use Exception;

class JwtAuth
{
    private $em;
    private $key;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em  = $em;
        $this->key = "KEY_BACK_SAVE_YOUTUBE_VIDEOS";
    }

    /**
     * @param $email
     * @param $password
     * @param $getToken
     *
     * @return object|string
     * @throws Exception
     *
     */
    public function signUp($email, $password, $getToken)
    {
        $user = $this->em->getRepository(User::class)->findOneBy([
            "email"    => $email,
            "password" => hash("sha256", $password)
        ]);

        if (!$user) {
            throw new Exception("Email or password invalid");
        }

        $token = [
            'sub'     => $user->getId(),
            'name'    => $user->getName(),
            'surname' => $user->getSurname(),
            'email'   => $user->getEmail(),
            'role'    => $user->getRole(),
            'iat'     => time(),
            'exp'     => time() + (7 * 24 * 60 * 60),
        ];

        $jwt = JWT::encode($token, $this->key, 'HS256');

        if ($getToken) {
            return $jwt;
        }

        return JWT::decode($jwt, $this->key, ['HS256']);
    }

    /**
     * @param $jwt
     * @param bool $returnUser
     *
     * @return bool|object
     */
    public function checkToken($jwt, $returnUser = false)
    {
        $auth = false;

        try {
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        } catch (Exception $e) {
            $auth = false;
        }

        if (isset($decoded) && isset($decoded->sub)) {
            if ($returnUser) {
                return $decoded;
            }

            $auth = true;
        }

        return $auth;
    }
}