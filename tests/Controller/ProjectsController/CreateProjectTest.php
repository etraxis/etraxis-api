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
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\ProjectsController::createProject
 */
class CreateProjectTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Awesome Express']);
        self::assertNull($project);

        $data = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $uri = '/api/projects';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Awesome Express']);
        self::assertNotNull($project);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->isRedirect("http://localhost/api/projects/{$project->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        $uri = '/api/projects';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        $data = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $uri = '/api/projects';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        $data = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $uri = '/api/projects';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'name'        => 'Distinctio',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $uri = '/api/projects';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
