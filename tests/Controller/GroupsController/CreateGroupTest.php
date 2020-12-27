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

namespace eTraxis\Controller\GroupsController;

use eTraxis\Entity\Group;
use eTraxis\Entity\Project;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\GroupsController::createGroup
 */
class CreateGroupTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Group $group */
        $group = $this->doctrine->getRepository(Group::class)->findOneBy(['name' => 'Testers']);
        static::assertNull($group);

        $data = [
            'project'     => $project->id,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $uri = '/api/groups';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        $group = $this->doctrine->getRepository(Group::class)->findOneBy(['name' => 'Testers']);
        static::assertNotNull($group);

        static::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        static::assertTrue($this->client->getResponse()->isRedirect("http://localhost/api/groups/{$group->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        $uri = '/api/groups';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        static::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $uri = '/api/groups';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $uri = '/api/groups';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('artem@example.com');

        $data = [
            'project'     => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $uri = '/api/groups';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Managers',
            'description' => 'Project management',
        ];

        $uri = '/api/groups';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
