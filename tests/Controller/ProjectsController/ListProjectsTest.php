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

namespace eTraxis\Controller\ProjectsController;

use eTraxis\Entity\Project;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\ProjectsController::listProjects
 */
class ListProjectsTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        $expected = array_map(fn (Project $project) => $project->name, $this->doctrine->getRepository(Project::class)->findAll());

        $uri = '/api/projects';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $actual  = array_map(fn (array $row) => $row['name'], $content['data']);

        self::assertSame(0, $content['from']);
        self::assertSame(3, $content['to']);
        self::assertSame(4, $content['total']);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        $uri = '/api/projects';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        $uri = '/api/projects';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
