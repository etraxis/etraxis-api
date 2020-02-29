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

namespace eTraxis\Controller\StatesController;

use eTraxis\Entity\State;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\StatesController::getTransitions
 */
class GetTransitionsTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $uri = sprintf('/api/states/%s/transitions', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent(), true);

        self::assertArrayHasKey('roles', $content);
        self::assertArrayHasKey('groups', $content);

        foreach ($content['roles'] as $entry) {
            self::assertArrayHasKey('state', $entry);
            self::assertArrayHasKey('role', $entry);
        }

        foreach ($content['groups'] as $entry) {
            self::assertArrayHasKey('state', $entry);
            self::assertArrayHasKey('group', $entry);
        }
    }

    public function test401()
    {
        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $uri = sprintf('/api/states/%s/transitions', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $uri = sprintf('/api/states/%s/transitions', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/transitions', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
