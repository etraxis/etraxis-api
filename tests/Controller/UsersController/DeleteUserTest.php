<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Controller\UsersController;

use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\UsersController::deleteUser
 */
class DeleteUserTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'hstroman@example.com']);
        static::assertNotNull($user);

        $uri = sprintf('/api/users/%s', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->clear();

        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'hstroman@example.com']);
        static::assertNull($user);
    }

    public function test401()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'hstroman@example.com']);

        $uri = sprintf('/api/users/%s', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'hstroman@example.com']);

        $uri = sprintf('/api/users/%s', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
