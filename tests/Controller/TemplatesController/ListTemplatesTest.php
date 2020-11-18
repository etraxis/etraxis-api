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

namespace eTraxis\Controller\TemplatesController;

use eTraxis\Entity\Template;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\TemplatesController::listTemplates
 */
class ListTemplatesTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        $expected = array_map(fn (Template $template) => [
            $template->name,
            $template->description,
        ], $this->doctrine->getRepository(Template::class)->findAll());

        $uri = '/api/templates';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $actual  = array_map(fn (array $row) => [
            $row['name'],
            $row['description'],
        ], $content['data']);

        self::assertSame(0, $content['from']);
        self::assertSame(7, $content['to']);
        self::assertSame(8, $content['total']);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        $uri = '/api/templates';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        $uri = '/api/templates';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
