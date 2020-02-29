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
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\FieldsController::listFields
 */
class ListFieldsTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        $expected = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $this->doctrine->getRepository(Field::class)->findBy(['removedAt' => null]));

        $uri = '/api/fields';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $actual  = array_map(function (array $row) {
            return [$row['name'], $row['state']['template']['project']['name']];
        }, $content['data']);

        self::assertSame(0, $content['from']);
        self::assertSame(39, $content['to']);
        self::assertSame(40, $content['total']);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        $uri = '/api/fields';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        $uri = '/api/fields';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
