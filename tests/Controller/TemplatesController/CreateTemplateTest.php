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

use eTraxis\Entity\Project;
use eTraxis\Entity\Template;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\TemplatesController::createTemplate
 */
class CreateTemplateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->doctrine->getRepository(Template::class)->findOneBy(['name' => 'Bugfix']);
        static::assertNull($template);

        $data = [
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ];

        $uri = '/api/templates';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        $template = $this->doctrine->getRepository(Template::class)->findOneBy(['name' => 'Bugfix']);
        static::assertNotNull($template);

        static::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        static::assertTrue($this->client->getResponse()->isRedirect("http://localhost/api/templates/{$template->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        $uri = '/api/templates';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        static::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ];

        $uri = '/api/templates';

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
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ];

        $uri = '/api/templates';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'project'     => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ];

        $uri = '/api/templates';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'task',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ];

        $uri = '/api/templates';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
