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
 * @covers \eTraxis\Controller\API\FieldsController::updateField
 */
class UpdateFieldTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        /** @var \eTraxis\Entity\FieldTypes\CheckboxInterface $facade */
        $facade = $field->getFacade($manager);
        self::assertFalse($facade->getDefaultValue());

        $data = [
            'name'     => $field->name,
            'required' => $field->isRequired,
            'default'  => true,
        ];

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        self::assertTrue($facade->getDefaultValue());
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $data = [
            'name'     => $field->name,
            'required' => $field->isRequired,
            'default'  => true,
        ];

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $data = [
            'name'     => $field->name,
            'required' => $field->isRequired,
            'default'  => true,
        ];

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $data = [
            'name'     => $field->name,
            'required' => $field->isRequired,
            'default'  => true,
        ];

        $uri = sprintf('/api/fields/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $data = [
            'name'     => 'Priority',
            'required' => $field->isRequired,
            'default'  => true,
        ];

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
