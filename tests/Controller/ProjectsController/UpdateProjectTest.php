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

namespace eTraxis\Controller\ProjectsController;

use eTraxis\Entity\Project;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\ProjectsController::updateProject
 */
class UpdateProjectTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'name'        => 'Awesome Express',
            'description' => $project->description,
            'suspended'   => $project->isSuspended,
        ];

        $uri = sprintf('/api/projects/%s', $project->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($project);

        self::assertSame('Awesome Express', $project->name);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $uri = sprintf('/api/projects/%s', $project->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'name'        => 'Awesome Express',
            'description' => $project->description,
            'suspended'   => $project->isSuspended,
        ];

        $uri = sprintf('/api/projects/%s', $project->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'name'        => 'Awesome Express',
            'description' => $project->description,
            'suspended'   => $project->isSuspended,
        ];

        $uri = sprintf('/api/projects/%s', $project->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'name'        => 'Awesome Express',
            'description' => $project->description,
            'suspended'   => $project->isSuspended,
        ];

        $uri = sprintf('/api/projects/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'name'        => 'Molestiae',
            'description' => $project->description,
            'suspended'   => $project->isSuspended,
        ];

        $uri = sprintf('/api/projects/%s', $project->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
