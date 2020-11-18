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
 * @covers \eTraxis\Controller\API\TemplatesController::updateTemplate
 */
class UpdateTemplateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['description' => 'ASC']);

        $data = [
            'name'        => 'Bugfix',
            'prefix'      => $template->prefix,
            'description' => $template->description,
            'critical'    => $template->criticalAge,
            'frozen'      => $template->frozenTime,
        ];

        $uri = sprintf('/api/templates/%s', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        self::assertSame('Bugfix', $template->name);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['description' => 'ASC']);

        $uri = sprintf('/api/templates/%s', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['description' => 'ASC']);

        $data = [
            'name'        => 'Bugfix',
            'prefix'      => $template->prefix,
            'description' => $template->description,
            'critical'    => $template->criticalAge,
            'frozen'      => $template->frozenTime,
        ];

        $uri = sprintf('/api/templates/%s', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['description' => 'ASC']);

        $data = [
            'name'        => 'Bugfix',
            'prefix'      => $template->prefix,
            'description' => $template->description,
            'critical'    => $template->criticalAge,
            'frozen'      => $template->frozenTime,
        ];

        $uri = sprintf('/api/templates/%s', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['description' => 'ASC']);

        $data = [
            'name'        => 'Bugfix',
            'prefix'      => $template->prefix,
            'description' => $template->description,
            'critical'    => $template->criticalAge,
            'frozen'      => $template->frozenTime,
        ];

        $uri = sprintf('/api/templates/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['description' => 'ASC']);

        $data = [
            'name'        => 'Support',
            'prefix'      => $template->prefix,
            'description' => $template->description,
            'critical'    => $template->criticalAge,
            'frozen'      => $template->frozenTime,
        ];

        $uri = sprintf('/api/templates/%s', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
