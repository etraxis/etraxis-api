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

/**
 * @coversDefaultClass \eTraxis\Serializer\ListItemNormalizer
 */
class ListItemNormalizerTest extends WebTestCase
{
    /**
     * @var ListItemNormalizer
     */
    private $normalizer;

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
        $fieldNormalizer    = new FieldNormalizer($security, $router, $manager, $stateNormalizer);
        $this->normalizer   = new ListItemNormalizer($security, $router, $fieldNormalizer);
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSelfOnly()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'normal'], ['id' => 'DESC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = str_replace('/api/fields', null, rtrim($router->generate('api_fields_list', [], UrlGeneratorInterface::ABSOLUTE_URL), '/'));

        $expected = [
            'id'    => $item->id,
            'value' => 2,
            'text'  => 'normal',
            'links' => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/items/%s', $baseUrl, $item->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($item, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinks()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'normal'], ['id' => 'DESC']);

        /** @var State $nextState */
        [$nextState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'DESC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = str_replace('/api/fields', null, rtrim($router->generate('api_fields_list', [], UrlGeneratorInterface::ABSOLUTE_URL), '/'));

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
                            'name'        => 'Presto',
                            'description' => 'Project D',
                            'created'     => $item->field->state->template->project->createdAt,
                            'suspended'   => false,
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
                        'description' => 'Development Task D',
                        'critical'    => null,
                        'frozen'      => null,
                        'locked'      => true,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/templates/%s', $baseUrl, $item->field->state->template->id),
                                'type' => 'GET',
                            ],
                        ],
                    ],
                    'name'        => 'New',
                    'type'        => 'intermediate',
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
                [
                    'rel'  => 'update',
                    'href' => sprintf('%s/api/items/%s', $baseUrl, $item->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'delete',
                    'href' => sprintf('%s/api/items/%s', $baseUrl, $item->id),
                    'type' => 'DELETE',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($item, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $project  = new Project();
        $template = new Template($project);
        $state    = new State($template, StateType::INTERMEDIATE);
        $field    = new Field($state, FieldType::LIST);
        $item     = new ListItem($field);

        self::assertTrue($this->normalizer->supportsNormalization($item, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($item, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization($field, 'json'));
    }
}
