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

use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\State;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\StatesController::setInitialState
 */
class SetInitialStateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);
        self::assertNotSame(StateType::INITIAL, $state->type);

        $uri = sprintf('/api/states/%s/initial', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($state);

        self::assertSame(StateType::INITIAL, $state->type);
    }

    public function test401()
    {
        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $uri = sprintf('/api/states/%s/initial', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $uri = sprintf('/api/states/%s/initial', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/initial', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
