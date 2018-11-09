<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\Config\UserConfig;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Controller to handle users.
 */
class UserController extends Controller
{
    /**
     * @var UserConfig
     */
    private $config;

    /**
     * Constructor.
     *
     * @param UserConfig $config
     */
    public function __construct(UserConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Returns a list of users in the configuration file.
     *
     * @return Response
     *
     * @Route("/users", methods={"GET"})
     */
    public function listUsers()
    {
        return $this->getUserResponse($this->config->getUsers());
    }

    /**
     * Adds a new user to the configuration file.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/users", methods={"POST"})
     */
    public function createUser(Request $request)
    {
        $user = $this->createUserFromRequest($request);

        if ($this->config->hasUser($user->getUsername())) {
            throw new BadRequestHttpException(sprintf('User "%s" already exists.', $user->getUsername()));
        }

        $this->config->addUser($user);

        return $this->getUserResponse($user, Response::HTTP_CREATED, true);
    }

    /**
     * Returns user data from the configuration file.
     *
     * @param string $username
     *
     * @return Response
     *
     * @Route("/users/{username}", name="user_get", methods={"GET"})
     */
    public function retrieveUser($username)
    {
        if ($this->config->hasUser($username)) {
            return $this->getUserResponse($this->config->getUser($username));
        }

        throw new NotFoundHttpException(sprintf('User "%s" was not found.', $username));
    }

    /**
     * Replaces user data in the configuration file.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/users/{username}", methods={"PUT"})
     */
    public function replaceUser(Request $request)
    {
        $user = $this->createUserFromRequest($request);

        if (!$this->config->hasUser($user->getUsername())) {
            throw new NotFoundHttpException(sprintf('User "%s" does not exist.', $user->getUsername()));
        }

        $this->config->updateUser($user);

        return $this->getUserResponse($user, Response::HTTP_OK, true);
    }

    /**
     * Deletes a user from the configuration file.
     *
     * @param string $username
     *
     * @return Response
     *
     * @Route("/users/{username}", methods={"DELETE"})
     */
    public function deleteUser($username)
    {
        $user = $this->config->getUser($username);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('User "%s" was not found.', $username));
        }

        $this->config->deleteUser($username);

        return $this->getUserResponse($user);
    }

    /**
     * Returns a list of tokens of a user in the configuration file.
     *
     * @param string $username
     *
     * @return Response
     *
     * @Route("/users/{username}/tokens", methods={"GET"})
     */
    public function listTokens($username)
    {
        $tokens = array_filter(
            $this->config->getTokens(),
            function ($token) use ($username) {
                return $token['user'] === $username;
            }
        );

        return new JsonResponse($tokens);
    }

    /**
     * Adds a new token for a user to the configuration file.
     *
     * @param string  $username
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/users/{username}/tokens", methods={"POST"})
     */
    public function createToken($username, Request $request)
    {
        if (!$this->config->hasUser($username)) {
            throw new BadRequestHttpException(sprintf('User "%s" does not exists.', $username));
        }

        $clientId = $request->request->get('client_id');
        $scope = $request->request->get('scope');

        if (!$clientId || $scope !== 'admin') {
            throw new BadRequestHttpException('Invalid payload for OAuth token.');
        }

        foreach ($this->config->getTokens() as $payload) {
            if ($payload['username'] === $username && $payload['client_id'] === $clientId) {
                $this->config->deleteToken($payload['id']);
            }
        }

        return new JsonResponse($this->config->createToken($username, $clientId, $scope), Response::HTTP_CREATED);
    }

    /**
     * Returns token data of a user from the configuration file.
     *
     * @param string $username
     * @param string $id
     *
     * @return Response
     *
     * @Route("/users/{username}/tokens/{id}", methods={"GET"})
     */
    public function retrieveToken($username, $id)
    {
        $payload = $this->config->getToken($id);

        if (null === $payload || $payload['username'] !== $username) {
            throw new NotFoundHttpException(sprintf('Token with ID "%s" was not found.', $id));
        }

        return new JsonResponse($payload);
    }

    /**
     * Deletes a user from the configuration file.
     *
     * @param string $username
     * @param string $id
     *
     * @return Response
     *
     * @Route("/users/{username}/tokens/{id}", methods={"DELETE"})
     */
    public function deleteToken($username, $id)
    {
        $payload = $this->config->getToken($id);

        if (null === $payload || $payload['username'] !== $username) {
            throw new NotFoundHttpException(sprintf('Token "%s" was not found.', $id));
        }

        $this->config->deleteToken($id);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Creates a response for given user information.
     *
     * @param UserInterface|UserInterface[] $user
     * @param int                           $status
     * @param bool                          $addLocation
     *
     * @return Response
     */
    private function getUserResponse($user, $status = Response::HTTP_OK, $addLocation = false)
    {
        $response = new JsonResponse(
            $this->convertToJson($user),
            $status
        );

        if ($addLocation && $user instanceof UserInterface) {
            $response->headers->set('Location', $this->generateUrl('user_get', ['username' => $user->getUsername()]));
        }

        return $response;
    }

    /**
     * Converts a user to JSON representation.
     *
     * @param UserInterface[]|UserInterface $user
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private function convertToJson($user)
    {
        if ($user instanceof UserInterface) {
            return [
                'username' => $user->getUsername(),
            ];
        }

        if (!is_array($user)) {
            throw new \InvalidArgumentException('Can only convert UserInterface or array of UserInterface');
        }

        foreach ((array) $user as $k => $item) {
            $user[$k] = $this->convertToJson($item);
        }

        return $user;
    }

    /**
     * Creates and returns a new user from request data.
     *
     * @param Request $request
     *
     * @throws BadRequestHttpException
     *
     * @return UserInterface
     */
    private function createUserFromRequest(Request $request)
    {
        if (!$request->request->has('username') || !$request->request->has('password')) {
            throw new BadRequestHttpException('Username and password must be given.');
        }

        return $this->config->createUser(
            $request->request->get('username'),
            $request->request->get('password')
        );
    }
}
