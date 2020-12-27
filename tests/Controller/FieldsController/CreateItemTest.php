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

use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\FieldsController::createItem
 */
class CreateItemTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        $item = $this->doctrine->getRepository(ListItem::class)->findOneBy(['value' => 4]);
        static::assertNull($item);

        $data = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        $item = $this->doctrine->getRepository(ListItem::class)->findOneBy(['value' => 4]);
        static::assertNotNull($item);

        static::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        static::assertTrue($this->client->getResponse()->isRedirect("http://localhost/api/items/{$item->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        static::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $uri = sprintf('/api/fields/%s/items', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'value' => 3,
            'text'  => 'typo',
        ];

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
