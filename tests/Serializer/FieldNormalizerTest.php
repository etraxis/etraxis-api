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

namespace eTraxis\Serializer;

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Application\Hateoas;
use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\FieldNormalizer
 */
class FieldNormalizerTest extends WebTestCase
{
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $security */
        $security = self::$container->get('security.authorization_checker');

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router = self::$container->get('router');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $projectNormalizer  = new ProjectNormalizer($security, $router);
        $templateNormalizer = new TemplateNormalizer($security, $router, $projectNormalizer);
        $stateNormalizer    = new StateNormalizer($security, $router, $templateNormalizer);
        $this->normalizer   = new FieldNormalizer($security, $router, $manager, $stateNormalizer);
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSelfOnly()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var State $nextState */
        [/* skipping */, $nextState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var ListItem $listItem */
        [/* skipping */, $listItem] = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'normal'], ['id' => 'ASC']);

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
                        'name'        => 'Molestiae',
                        'description' => 'Project B',
                        'created'     => $field->state->template->project->createdAt,
                        'suspended'   => false,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/projects/%s', $baseUrl, $field->state->template->project->id),
                                'type' => 'GET',
                            ],
                        ],
                    ],
                    'name'        => 'Development',
                    'prefix'      => 'task',
                    'description' => 'Development Task B',
                    'critical'    => null,
                    'frozen'      => null,
                    'locked'      => true,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/templates/%s', $baseUrl, $field->state->template->id),
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
                        'href' => sprintf('%s/api/states/%s', $baseUrl, $field->state->id),
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
                'id'    => $listItem->id,
                'value' => 2,
                'text'  => 'normal',
            ],
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/fields/%s', $baseUrl, $field->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($field, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinksByAdmin()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var State $nextState */
        [/* skipping */, $nextState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var ListItem $listItem */
        [/* skipping */, $listItem] = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'normal'], ['id' => 'ASC']);

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
                        'name'        => 'Molestiae',
                        'description' => 'Project B',
                        'created'     => $field->state->template->project->createdAt,
                        'suspended'   => false,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/projects/%s', $baseUrl, $field->state->template->project->id),
                                'type' => 'GET',
                            ],
                        ],
                    ],
                    'name'        => 'Development',
                    'prefix'      => 'task',
                    'description' => 'Development Task B',
                    'critical'    => null,
                    'frozen'      => null,
                    'locked'      => true,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/templates/%s', $baseUrl, $field->state->template->id),
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
                        'href' => sprintf('%s/api/states/%s', $baseUrl, $field->state->id),
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
                'id'    => $listItem->id,
                'value' => 2,
                'text'  => 'normal',
            ],
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/fields/%s', $baseUrl, $field->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'update',
                    'href' => sprintf('%s/api/fields/%s', $baseUrl, $field->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'delete',
                    'href' => sprintf('%s/api/fields/%s', $baseUrl, $field->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'  => 'set_position',
                    'href' => sprintf('%s/api/fields/%s/position', $baseUrl, $field->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'get_permissions',
                    'href' => sprintf('%s/api/fields/%s/permissions', $baseUrl, $field->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'set_permissions',
                    'href' => sprintf('%s/api/fields/%s/permissions', $baseUrl, $field->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'items',
                    'href' => sprintf('%s/api/fields/%s/items', $baseUrl, $field->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'create_item',
                    'href' => sprintf('%s/api/fields/%s/items', $baseUrl, $field->id),
                    'type' => 'POST',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($field, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinksByDeveloper()
    {
        $this->loginAs('fdooley@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var State $nextState */
        [/* skipping */, $nextState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var ListItem $listItem */
        [/* skipping */, $listItem] = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'normal'], ['id' => 'ASC']);

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
                        'name'        => 'Molestiae',
                        'description' => 'Project B',
                        'created'     => $field->state->template->project->createdAt,
                        'suspended'   => false,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/projects/%s', $baseUrl, $field->state->template->project->id),
                                'type' => 'GET',
                            ],
                        ],
                    ],
                    'name'        => 'Development',
                    'prefix'      => 'task',
                    'description' => 'Development Task B',
                    'critical'    => null,
                    'frozen'      => null,
                    'locked'      => true,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/templates/%s', $baseUrl, $field->state->template->id),
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
                        'href' => sprintf('%s/api/states/%s', $baseUrl, $field->state->id),
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
                'id'    => $listItem->id,
                'value' => 2,
                'text'  => 'normal',
            ],
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/fields/%s', $baseUrl, $field->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'items',
                    'href' => sprintf('%s/api/fields/%s/items', $baseUrl, $field->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($field, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $project  = new Project();
        $template = new Template($project);
        $state    = new State($template, StateType::INTERMEDIATE);
        $field    = new Field($state, FieldType::STRING);

        self::assertTrue($this->normalizer->supportsNormalization($field, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($field, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization($state, 'json'));
    }
}
