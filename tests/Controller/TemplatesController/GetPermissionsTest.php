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
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\TemplatesController::getPermissions
 */
class GetPermissionsTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent(), true);

        self::assertArrayHasKey('roles', $content);
        self::assertArrayHasKey('groups', $content);

        foreach ($content['roles'] as $entry) {
            self::assertArrayHasKey('role', $entry);
            self::assertArrayHasKey('permission', $entry);
        }

        foreach ($content['groups'] as $entry) {
            self::assertArrayHasKey('group', $entry);
            self::assertArrayHasKey('permission', $entry);
        }
    }

    public function test401()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s/permissions', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
