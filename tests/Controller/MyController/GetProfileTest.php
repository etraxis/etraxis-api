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

namespace eTraxis\Controller\MyController;

use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\MyController::getProfile
 */
class GetProfileTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $expected = [
            'id'         => $user->id,
            'email'      => 'artem@example.com',
            'fullname'   => 'Artem Rodygin',
            'provider'   => 'etraxis',
            'locale'     => 'en_US',
            'theme'      => 'azure',
            'light_mode' => true,
            'timezone'   => 'UTC',
        ];

        $uri = '/api/my/profile';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function testSuccessExternal()
    {
        $this->loginAs('einstein@ldap.forumsys.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'einstein@ldap.forumsys.com']);

        $expected = [
            'id'         => $user->id,
            'email'      => 'einstein@ldap.forumsys.com',
            'fullname'   => 'Albert Einstein',
            'provider'   => 'ldap',
            'locale'     => 'en_US',
            'theme'      => 'azure',
            'light_mode' => true,
            'timezone'   => 'UTC',
        ];

        $uri = '/api/my/profile';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function test401()
    {
        $uri = '/api/my/profile';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
