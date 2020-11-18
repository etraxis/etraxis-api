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

namespace eTraxis\Serializer;

use eTraxis\Application\Dictionary\StateType;
use eTraxis\Application\Hateoas;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\StateNormalizer
 */
class StateNormalizerTest extends WebTestCase
{
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $security */
        $security = self::$container->get('security.authorization_checker');

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router = self::$container->get('router');

        $projectNormalizer  = new ProjectNormalizer($security, $router);
        $templateNormalizer = new TemplateNormalizer($security, $router, $projectNormalizer);
        $this->normalizer   = new StateNormalizer($security, $router, $templateNormalizer);
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSelfOnly()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'DESC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $state->id,
            'template'    => [
                'id'          => $state->template->id,
                'project'     => [
                    'id'          => $state->template->project->id,
                    'name'        => 'Presto',
                    'description' => 'Project D',
                    'created'     => $state->template->project->createdAt,
                    'suspended'   => false,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/projects/%s', $baseUrl, $state->template->project->id),
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
                        'href' => sprintf('%s/api/templates/%s', $baseUrl, $state->template->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'name'        => 'Assigned',
            'type'        => 'intermediate',
            'responsible' => 'assign',
            'next'        => null,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/states/%s', $baseUrl, $state->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($state, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinks()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'DESC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $state->id,
            'template'    => [
                'id'          => $state->template->id,
                'project'     => [
                    'id'          => $state->template->project->id,
                    'name'        => 'Presto',
                    'description' => 'Project D',
                    'created'     => $state->template->project->createdAt,
                    'suspended'   => false,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/projects/%s', $baseUrl, $state->template->project->id),
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
                        'href' => sprintf('%s/api/templates/%s', $baseUrl, $state->template->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'name'        => 'Assigned',
            'type'        => 'intermediate',
            'responsible' => 'assign',
            'next'        => null,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/states/%s', $baseUrl, $state->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'update',
                    'href' => sprintf('%s/api/states/%s', $baseUrl, $state->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'delete',
                    'href' => sprintf('%s/api/states/%s', $baseUrl, $state->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'  => 'set_initial',
                    'href' => sprintf('%s/api/states/%s/initial', $baseUrl, $state->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'get_transitions',
                    'href' => sprintf('%s/api/states/%s/transitions', $baseUrl, $state->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'set_transitions',
                    'href' => sprintf('%s/api/states/%s/transitions', $baseUrl, $state->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'get_responsible_groups',
                    'href' => sprintf('%s/api/states/%s/responsibles', $baseUrl, $state->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'set_responsible_groups',
                    'href' => sprintf('%s/api/states/%s/responsibles', $baseUrl, $state->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'create_field',
                    'href' => sprintf('%s/api/fields', $baseUrl),
                    'type' => 'POST',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($state, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $project  = new Project();
        $template = new Template($project);
        $state    = new State($template, StateType::INTERMEDIATE);

        self::assertTrue($this->normalizer->supportsNormalization($state, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($state, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization($template, 'json'));
    }
}
