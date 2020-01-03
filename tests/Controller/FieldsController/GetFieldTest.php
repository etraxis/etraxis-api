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

namespace eTraxis\Controller\FieldsController;

use eTraxis\Entity\Field;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \eTraxis\Controller\FieldsController::getField
 */
class GetFieldTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $field->id,
            'state'       => [
                'id'          => $field->state->id,
                'template'    => [
                    'id'          => $field->state->template->id,
                    'project'     => [
                        'id'          => $field->state->template->project->id,
                        'name'        => 'Distinctio',
                        'description' => 'Project A',
                        'created'     => $field->state->template->project->createdAt,
                        'suspended'   => true,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/projects/%s', $baseUrl, $field->state->template->project->id),
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
                            'href' => sprintf('%s/api/templates/%s', $baseUrl, $field->state->template->id),
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
                        'href' => sprintf('%s/api/states/%s', $baseUrl, $field->state->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'name'        => 'Details',
            'type'        => 'text',
            'description' => null,
            'position'    => 1,
            'required'    => true,
            'maxlength'   => 250,
            'default'     => null,
            'pcre'        => [
                'check'   => null,
                'search'  => null,
                'replace' => null,
            ],
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/fields/%s', $baseUrl, $field->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'field.update',
                    'href' => sprintf('%s/api/fields/%s', $baseUrl, $field->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'field.delete',
                    'href' => sprintf('%s/api/fields/%s', $baseUrl, $field->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'  => 'field.permissions',
                    'href' => sprintf('%s/api/fields/%s/permissions', $baseUrl, $field->id),
                    'type' => 'GET',
                ],
            ],
        ];

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function test401()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);

        $uri = sprintf('/api/fields/%s', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
