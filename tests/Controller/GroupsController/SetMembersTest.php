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

namespace eTraxis\Controller\GroupsController;

use eTraxis\Entity\Group;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\GroupsController::setMembers
 */
class SetMembersTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers']);

        $expected = [
            'Berenice O\'Connell',
            'Dangelo Hill',
            'Dorcas Ernser',
            'Leland Doyle',
        ];

        $actual = array_map(fn (User $user) => $user->fullname, $group->members);

        static::assertSame($expected, $actual);

        /** @var User $ldoyle */
        $ldoyle = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $dquigley */
        $dquigley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        $data = [
            'add'    => [
                $dquigley->id,
            ],
            'remove' => [
                $ldoyle->id,
            ],
        ];

        $uri = sprintf('/api/groups/%s/members', $group->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($group);

        $expected = [
            'Berenice O\'Connell',
            'Dangelo Hill',
            'Dennis Quigley',
            'Dorcas Ernser',
        ];

        $actual = array_map(fn (User $user) => $user->fullname, $group->members);

        static::assertSame($expected, $actual);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers']);

        $data = [
            'add'    => [
                'foo',
            ],
            'remove' => [
                'bar',
            ],
        ];

        $uri = sprintf('/api/groups/%s/members', $group->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        static::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers']);

        /** @var User $ldoyle */
        $ldoyle = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $dquigley */
        $dquigley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        $data = [
            'add'    => [
                $dquigley->id,
            ],
            'remove' => [
                $ldoyle->id,
            ],
        ];

        $uri = sprintf('/api/groups/%s/members', $group->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers']);

        /** @var User $ldoyle */
        $ldoyle = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $dquigley */
        $dquigley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        $data = [
            'add'    => [
                $dquigley->id,
            ],
            'remove' => [
                $ldoyle->id,
            ],
        ];

        $uri = sprintf('/api/groups/%s/members', $group->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        /** @var User $ldoyle */
        $ldoyle = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $dquigley */
        $dquigley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        $data = [
            'add'    => [
                $dquigley->id,
            ],
            'remove' => [
                $ldoyle->id,
            ],
        ];

        $uri = sprintf('/api/groups/%s/members', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
