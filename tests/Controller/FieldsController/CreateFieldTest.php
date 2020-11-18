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

namespace eTraxis\Controller\FieldsController;

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\FieldsController::createField
 */
class CreateFieldTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->doctrine->getRepository(Field::class)->findOneBy(['name' => 'Week number']);
        self::assertNull($field);

        $data = [
            'state'    => $state->id,
            'type'     => FieldType::NUMBER,
            'name'     => 'Week number',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        $field = $this->doctrine->getRepository(Field::class)->findOneBy(['name' => 'Week number']);
        self::assertNotNull($field);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->isRedirect("http://localhost/api/fields/{$field->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        $uri = '/api/fields';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $data = [
            'state'    => $state->id,
            'type'     => FieldType::NUMBER,
            'name'     => 'Week number',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $data = [
            'state'    => $state->id,
            'type'     => FieldType::NUMBER,
            'name'     => 'Week number',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'state'    => self::UNKNOWN_ENTITY_ID,
            'type'     => FieldType::NUMBER,
            'name'     => 'Week number',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $data = [
            'state'    => $state->id,
            'type'     => FieldType::NUMBER,
            'name'     => 'Due date',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
