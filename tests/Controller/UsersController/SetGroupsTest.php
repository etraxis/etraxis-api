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

use eTraxis\Entity\Group;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\UsersController::setGroups
 */
class SetGroupsTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $expected = [
            ['Company Staff',     null],
            ['Developers',        'Developers C'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
        ];

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $user->groups);

        self::assertSame($expected, $actual);

        /** @var Group[] $support */
        $support = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $data = [
            'add'    => [
                $support[1]->id,
                $support[2]->id,
            ],
            'remove' => [
                $support[0]->id,
                $support[1]->id,
            ],
        ];

        $uri = sprintf('/api/users/%s/groups', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($user);

        $expected = [
            ['Company Staff',     null],
            ['Developers',        'Developers C'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
        ];

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $user->groups);

        self::assertSame($expected, $actual);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'add'    => [
                'bar1',
                'bar2',
            ],
            'remove' => [
                'foo1',
                'foo2',
            ],
        ];

        $uri = sprintf('/api/users/%s/groups', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var Group[] $support */
        $support = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $data = [
            'add'    => [
                $support[1]->id,
                $support[2]->id,
            ],
            'remove' => [
                $support[0]->id,
                $support[1]->id,
            ],
        ];

        $uri = sprintf('/api/users/%s/groups', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var Group[] $support */
        $support = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $data = [
            'add'    => [
                $support[1]->id,
                $support[2]->id,
            ],
            'remove' => [
                $support[0]->id,
                $support[1]->id,
            ],
        ];

        $uri = sprintf('/api/users/%s/groups', $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        /** @var Group[] $support */
        $support = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $data = [
            'add'    => [
                $support[1]->id,
                $support[2]->id,
            ],
            'remove' => [
                $support[0]->id,
                $support[1]->id,
            ],
        ];

        $uri = sprintf('/api/users/%s/groups', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
