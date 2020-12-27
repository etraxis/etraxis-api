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
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\MyController::updateProfile
 */
class UpdateProfileTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('nhills@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        static::assertSame('nhills@example.com', $user->email);
        static::assertSame('Nikko Hills', $user->fullname);
        static::assertSame('en_US', $user->locale);
        static::assertSame('azure', $user->theme);
        static::assertTrue($user->isLightMode);
        static::assertSame('UTC', $user->timezone);

        $uri = '/api/my/profile';

        $data = [
            'email'      => 'chaim.willms@example.com',
            'fullname'   => 'Chaim Willms',
            'locale'     => 'ru',
            'theme'      => 'emerald',
            'light_mode' => false,
            'timezone'   => 'Asia/Vladivostok',
        ];

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($user);

        static::assertSame('chaim.willms@example.com', $user->email);
        static::assertSame('Chaim Willms', $user->fullname);
        static::assertSame('ru', $user->locale);
        static::assertSame('emerald', $user->theme);
        static::assertFalse($user->isLightMode);
        static::assertSame('Asia/Vladivostok', $user->timezone);
    }

    public function testSuccessPartial()
    {
        $this->loginAs('einstein@ldap.forumsys.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'einstein@ldap.forumsys.com']);

        static::assertSame('einstein@ldap.forumsys.com', $user->email);
        static::assertSame('Albert Einstein', $user->fullname);
        static::assertSame('en_US', $user->locale);
        static::assertSame('azure', $user->theme);
        static::assertTrue($user->isLightMode);
        static::assertSame('UTC', $user->timezone);

        $uri = '/api/my/profile';

        $data = [
            'email'      => 'chaim.willms@example.com',
            'fullname'   => 'Chaim Willms',
            'locale'     => 'ru',
            'theme'      => 'emerald',
            'light_mode' => false,
            'timezone'   => 'Asia/Vladivostok',
        ];

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($user);

        static::assertSame('einstein@ldap.forumsys.com', $user->email);
        static::assertSame('Albert Einstein', $user->fullname);
        static::assertSame('ru', $user->locale);
        static::assertSame('emerald', $user->theme);
        static::assertFalse($user->isLightMode);
        static::assertSame('Asia/Vladivostok', $user->timezone);
    }

    public function test400()
    {
        $this->loginAs('nhills@example.com');

        $uri = '/api/my/profile';

        $data = [
            'email'      => 'invalid',
            'fullname'   => 'Chaim Willms',
            'locale'     => 'ru',
            'theme'      => 'emerald',
            'light_mode' => false,
            'timezone'   => 'Asia/Vladivostok',
        ];

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        static::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        $uri = '/api/my/profile';

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('nhills@example.com');

        $uri = '/api/my/profile';

        $data = [
            'email'      => 'ldoyle@example.com',
            'fullname'   => 'Chaim Willms',
            'locale'     => 'ru',
            'theme'      => 'emerald',
            'light_mode' => false,
            'timezone'   => 'Asia/Vladivostok',
        ];

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        static::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
