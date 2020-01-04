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

namespace eTraxis\Controller\GroupsController;

use eTraxis\Entity\Group;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \eTraxis\Controller\GroupsController::getGroup
 */
class GetGroupTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['description' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $group->id,
            'project'     => [
                'id'          => $group->project->id,
                'name'        => 'Distinctio',
                'description' => 'Project A',
                'created'     => $group->project->createdAt,
                'suspended'   => true,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/projects/%s', $baseUrl, $group->project->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'name'        => 'Managers',
            'description' => 'Managers A',
            'global'      => false,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/groups/%s', $baseUrl, $group->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'group.update',
                    'href' => sprintf('%s/api/groups/%s', $baseUrl, $group->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'group.delete',
                    'href' => sprintf('%s/api/groups/%s', $baseUrl, $group->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'  => 'group.membership',
                    'href' => sprintf('%s/api/groups/%s/members', $baseUrl, $group->id),
                    'type' => 'PATCH',
                ],
            ],
        ];

        $uri = sprintf('/api/groups/%s', $group->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function test401()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['description' => 'ASC']);

        $uri = sprintf('/api/groups/%s', $group->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['description' => 'ASC']);

        $uri = sprintf('/api/groups/%s', $group->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/groups/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
