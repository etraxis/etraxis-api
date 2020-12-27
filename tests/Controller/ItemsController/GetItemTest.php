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

namespace eTraxis\Controller\ItemsController;

use eTraxis\Entity\ListItem;
use eTraxis\Entity\State;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \eTraxis\Controller\API\ItemsController::getItem
 */
class GetItemTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        /** @var State $nextState */
        [$nextState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'    => $item->id,
            'value' => 2,
            'text'  => 'normal',
            'field' => [
                'id'          => $item->field->id,
                'state'       => [
                    'id'          => $item->field->state->id,
                    'template'    => [
                        'id'          => $item->field->state->template->id,
                        'project'     => [
                            'id'          => $item->field->state->template->project->id,
                            'name'        => 'Distinctio',
                            'description' => 'Project A',
                            'created'     => $item->field->state->template->project->createdAt,
                            'suspended'   => true,
                            'links'       => [
                                [
                                    'rel'  => 'self',
                                    'href' => sprintf('%s/api/projects/%s', $baseUrl, $item->field->state->template->project->id),
                                    'type' => 'GET',
                                ],
                            ],
                        ],
                        'name'        => 'Development',
                        'prefix'      => 'task',
                        'description' => 'Development Task A',
                        'critical'    => null,
                        'frozen'      => null,
                        'locked'      => false,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/templates/%s', $baseUrl, $item->field->state->template->id),
                                'type' => 'GET',
                            ],
                        ],
                    ],
                    'name'        => 'New',
                    'type'        => 'initial',
                    'responsible' => 'remove',
                    'next'        => $nextState->id,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/states/%s', $baseUrl, $item->field->state->id),
                            'type' => 'GET',
                        ],
                    ],
                ],
                'name'        => 'Priority',
                'type'        => 'list',
                'description' => null,
                'position'    => 1,
                'required'    => true,
                'default'     => [
                    'id'    => $item->id,
                    'value' => 2,
                    'text'  => 'normal',
                ],
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/fields/%s', $baseUrl, $item->field->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'links' => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/items/%s', $baseUrl, $item->id),
                    'type' => 'GET',
                ],
            ],
        ];

        $uri = sprintf('/api/items/%s', $item->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        static::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function test401()
    {
        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $uri = sprintf('/api/items/%s', $item->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $uri = sprintf('/api/items/%s', $item->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/items/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
