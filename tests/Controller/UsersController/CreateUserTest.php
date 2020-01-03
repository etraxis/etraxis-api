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

/**
 * @covers \eTraxis\Controller\UsersController::createUser
 */
class CreateUserTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNull($user);

        $data = [
            'email'       => 'anna@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'theme'       => 'humanity',
            'timezone'    => 'Pacific/Auckland',
        ];

        $uri = '/api/users';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNotNull($user);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->isRedirect("http://localhost/api/users/{$user->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        $uri = '/api/users';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        $data = [
            'email'       => 'anna@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'theme'       => 'humanity',
            'timezone'    => 'Pacific/Auckland',
        ];

        $uri = '/api/users';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        $data = [
            'email'       => 'anna@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'theme'       => 'humanity',
            'timezone'    => 'Pacific/Auckland',
        ];

        $uri = '/api/users';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'email'       => 'artem@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'theme'       => 'humanity',
            'timezone'    => 'Pacific/Auckland',
        ];

        $uri = '/api/users';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
