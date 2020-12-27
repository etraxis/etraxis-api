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

namespace eTraxis\Controller\StatesController;

use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Entity\Group;
use eTraxis\Entity\State;
use eTraxis\Entity\StateGroupTransition;
use eTraxis\Entity\StateRoleTransition;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\StatesController::setTransitions
 */
class SetTransitionsTest extends TransactionalTestCase
{
    public function testSuccessAll()
    {
        $this->loginAs('admin@example.com');

        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['description' => 'ASC']);

        $roles  = array_filter($stateFrom->roleTransitions, fn (StateRoleTransition $transition) => $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR);
        $groups = array_filter($stateFrom->groupTransitions, fn (StateGroupTransition $transition) => $transition->toState === $stateTo && $transition->group === $group);

        static::assertEmpty($roles);
        static::assertEmpty($groups);

        $data = [
            'state'  => $stateTo->id,
            'roles'  => [
                SystemRole::AUTHOR,
            ],
            'groups' => [
                $group->id,
            ],
        ];

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($stateFrom);

        $roles  = array_filter($stateFrom->roleTransitions, fn (StateRoleTransition $transition) => $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR);
        $groups = array_filter($stateFrom->groupTransitions, fn (StateGroupTransition $transition) => $transition->toState === $stateTo && $transition->group === $group);

        static::assertNotEmpty($roles);
        static::assertNotEmpty($groups);
    }

    public function testSuccessRoles()
    {
        $this->loginAs('admin@example.com');

        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['description' => 'ASC']);

        $roles  = array_filter($stateFrom->roleTransitions, fn (StateRoleTransition $transition) => $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR);
        $groups = array_filter($stateFrom->groupTransitions, fn (StateGroupTransition $transition) => $transition->toState === $stateTo && $transition->group === $group);

        static::assertEmpty($roles);
        static::assertEmpty($groups);

        $data = [
            'state' => $stateTo->id,
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($stateFrom);

        $roles  = array_filter($stateFrom->roleTransitions, fn (StateRoleTransition $transition) => $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR);
        $groups = array_filter($stateFrom->groupTransitions, fn (StateGroupTransition $transition) => $transition->toState === $stateTo && $transition->group === $group);

        static::assertNotEmpty($roles);
        static::assertEmpty($groups);
    }

    public function testSuccessGroups()
    {
        $this->loginAs('admin@example.com');

        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['description' => 'ASC']);

        $roles  = array_filter($stateFrom->roleTransitions, fn (StateRoleTransition $transition) => $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR);
        $groups = array_filter($stateFrom->groupTransitions, fn (StateGroupTransition $transition) => $transition->toState === $stateTo && $transition->group === $group);

        static::assertEmpty($roles);
        static::assertEmpty($groups);

        $data = [
            'state'  => $stateTo->id,
            'groups' => [
                $group->id,
            ],
        ];

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($stateFrom);

        $roles  = array_filter($stateFrom->roleTransitions, fn (StateRoleTransition $transition) => $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR);
        $groups = array_filter($stateFrom->groupTransitions, fn (StateGroupTransition $transition) => $transition->toState === $stateTo && $transition->group === $group);

        static::assertEmpty($roles);
        static::assertNotEmpty($groups);
    }

    public function testSuccessNone()
    {
        $this->loginAs('admin@example.com');

        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['description' => 'ASC']);

        $roles  = array_filter($stateFrom->roleTransitions, fn (StateRoleTransition $transition) => $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR);
        $groups = array_filter($stateFrom->groupTransitions, fn (StateGroupTransition $transition) => $transition->toState === $stateTo && $transition->group === $group);

        static::assertEmpty($roles);
        static::assertEmpty($groups);

        $data = [
            'state' => $stateTo->id,
        ];

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($stateFrom);

        $roles  = array_filter($stateFrom->roleTransitions, fn (StateRoleTransition $transition) => $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR);
        $groups = array_filter($stateFrom->groupTransitions, fn (StateGroupTransition $transition) => $transition->toState === $stateTo && $transition->group === $group);

        static::assertEmpty($roles);
        static::assertEmpty($groups);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $data = [
            'state' => $stateTo->id,
            'roles' => [
                'unknown',
            ],
        ];

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $data = [
            'state' => $stateTo->id,
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $data = [
            'state' => $stateTo->id,
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $data = [
            'state' => $stateTo->id,
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/states/%s/transitions', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
