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

namespace eTraxis\Controller\FieldsController;

use eTraxis\Entity\Field;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\FieldsController::deleteField
 */
class DeleteFieldTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);
        self::assertFalse($field->isRemoved);

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        self::assertTrue($field->isRemoved);
    }

    public function test401()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
