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

namespace eTraxis\Controller\TemplatesController;

use eTraxis\Entity\Template;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \eTraxis\Controller\TemplatesController::getTemplate
 */
class GetTemplateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $template->id,
            'project'     => [
                'id'          => $template->project->id,
                'name'        => 'Distinctio',
                'description' => 'Project A',
                'created'     => $template->project->createdAt,
                'suspended'   => true,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/projects/%s', $baseUrl, $template->project->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'name'        => 'Support',
            'prefix'      => 'req',
            'description' => 'Support Request A',
            'critical'    => 3,
            'frozen'      => 7,
            'locked'      => true,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/templates/%s', $baseUrl, $template->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'template.update',
                    'href' => sprintf('%s/api/templates/%s', $baseUrl, $template->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'template.lock',
                    'href' => sprintf('%s/api/templates/%s/lock', $baseUrl, $template->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'template.unlock',
                    'href' => sprintf('%s/api/templates/%s/unlock', $baseUrl, $template->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'template.permissions',
                    'href' => sprintf('%s/api/templates/%s/permissions', $baseUrl, $template->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'state.create',
                    'href' => sprintf('%s/api/states', $baseUrl),
                    'type' => 'POST',
                ],
            ],
        ];

        $uri = sprintf('/api/templates/%s', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function test401()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $uri = sprintf('/api/templates/%s', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $uri = sprintf('/api/templates/%s', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
