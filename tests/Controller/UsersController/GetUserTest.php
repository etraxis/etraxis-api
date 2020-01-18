<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Controller\UsersController;

use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \eTraxis\Controller\UsersController::retrieveUser
 */
class GetUserTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = str_replace('/api/users', null, rtrim($router->generate('api_users_list', [], UrlGeneratorInterface::ABSOLUTE_URL), '/'));

        $expected = [
            'id'          => $user->id,
            'email'       => 'artem@example.com',
            'fullname'    => 'Artem Rodygin',
            'description' => null,
            'admin'       => false,
            'disabled'    => false,
            'locked'      => false,
            'provider'    => 'etraxis',
            'locale'      => 'en_US',
            'theme'       => 'azure',
            'timezone'    => 'UTC',
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/users/%s', $baseUrl, $user->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'update',
                    'href' => sprintf('%s/api/users/%s', $baseUrl, $user->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'delete',
                    'href' => sprintf('%s/api/users/%s', $baseUrl, $user->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'  => 'disable',
                    'href' => sprintf('%s/api/users/%s/disable', $baseUrl, $user->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'set_password',
                    'href' => sprintf('%s/api/users/%s/password', $baseUrl, $user->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'groups',
                    'href' => sprintf('%s/api/users/%s/groups', $baseUrl, $user->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'add_groups',
                    'href' => sprintf('%s/api/users/%s/groups', $baseUrl, $user->id),
                    'type' => 'PATCH',
                ],
                [
                    'rel'  => 'remove_groups',
                    'href' => sprintf('%s/api/users/%s/groups', $baseUrl, $user->id),
                    'type' => 'PATCH',
                ],
            ],
        ];

        $uri = sprintf('/api/users/%s', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function test401()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $uri = sprintf('/api/users/%s', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $uri = sprintf('/api/users/%s', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/users/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
