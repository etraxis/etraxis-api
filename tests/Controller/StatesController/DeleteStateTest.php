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
 * @covers \eTraxis\Controller\API\StatesController::deleteState
 */
class DeleteStateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);
        self::assertNotNull($state);

        $id = $state->id;

        $uri = sprintf('/api/states/%s', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertNull($this->doctrine->getRepository(State::class)->find($id));
    }

    public function test401()
    {
        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);

        $uri = sprintf('/api/states/%s', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);

        $uri = sprintf('/api/states/%s', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
