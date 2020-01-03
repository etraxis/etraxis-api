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

namespace eTraxis\Controller\StatesController;

use eTraxis\Entity\State;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \eTraxis\Controller\StatesController::getState
 */
class GetStateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $state->id,
            'template'    => [
                'id'          => $state->template->id,
                'project'     => [
                    'id'          => $state->template->project->id,
                    'name'        => 'Distinctio',
                    'description' => 'Project A',
                    'created'     => $state->template->project->createdAt,
                    'suspended'   => true,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/projects/%s', $baseUrl, $state->template->project->id),
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
                        'href' => sprintf('%s/api/templates/%s', $baseUrl, $state->template->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'name'        => 'Submitted',
            'type'        => 'initial',
            'responsible' => 'keep',
            'next'        => null,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/states/%s', $baseUrl, $state->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'state.update',
                    'href' => sprintf('%s/api/states/%s', $baseUrl, $state->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'state.set_initial',
                    'href' => sprintf('%s/api/states/%s/initial', $baseUrl, $state->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'state.transitions',
                    'href' => sprintf('%s/api/states/%s/transitions', $baseUrl, $state->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'field.create',
                    'href' => sprintf('%s/api/fields', $baseUrl),
                    'type' => 'POST',
                ],
            ],
        ];

        $uri = sprintf('/api/states/%s', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function test401()
    {
        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        $uri = sprintf('/api/states/%s', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        $uri = sprintf('/api/states/%s', $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
