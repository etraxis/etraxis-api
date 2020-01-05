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

use eTraxis\Application\Hateoas;
use eTraxis\Entity\Project;
use eTraxis\Entity\Template;
use eTraxis\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\TemplateNormalizer
 */
class TemplateNormalizerTest extends WebTestCase
{
    /**
     * @var TemplateNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $security */
        $security = self::$container->get('security.authorization_checker');

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router = self::$container->get('router');

        $projectNormalizer = new ProjectNormalizer($security, $router);
        $this->normalizer  = new TemplateNormalizer($security, $router, $projectNormalizer);
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSelfOnly()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'DESC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $template->id,
            'project'     => [
                'id'          => $template->project->id,
                'name'        => 'Presto',
                'description' => 'Project D',
                'created'     => $template->project->createdAt,
                'suspended'   => false,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/projects/%s', $baseUrl, $template->project->id),
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
                    'href' => sprintf('%s/api/templates/%s', $baseUrl, $template->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($template, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinksUnlocked()
    {
        $this->loginAs('nhills@example.com');

        /** @var Template $template */
        [/* skipping */, /* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $template->id,
            'project'     => [
                'id'          => $template->project->id,
                'name'        => 'Excepturi',
                'description' => 'Project C',
                'created'     => $template->project->createdAt,
                'suspended'   => false,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/projects/%s', $baseUrl, $template->project->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'name'        => 'Development',
            'prefix'      => 'task',
            'description' => 'Development Task C',
            'critical'    => null,
            'frozen'      => null,
            'locked'      => false,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/templates/%s', $baseUrl, $template->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'issue.create',
                    'href' => sprintf('%s/api/issues', $baseUrl),
                    'type' => 'POST',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($template, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinksLocked()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'DESC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $template->id,
            'project'     => [
                'id'          => $template->project->id,
                'name'        => 'Presto',
                'description' => 'Project D',
                'created'     => $template->project->createdAt,
                'suspended'   => false,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/projects/%s', $baseUrl, $template->project->id),
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
                    'href' => sprintf('%s/api/templates/%s', $baseUrl, $template->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'template.update',
                    'href' => sprintf('%s/api/templates/%s', $baseUrl, $template->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'template.delete',
                    'href' => sprintf('%s/api/templates/%s', $baseUrl, $template->id),
                    'type' => 'DELETE',
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
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'state.create',
                    'href' => sprintf('%s/api/states', $baseUrl),
                    'type' => 'POST',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($template, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $project  = new Project();
        $template = new Template($project);

        self::assertTrue($this->normalizer->supportsNormalization($template, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($template, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization(new \stdClass(), 'json'));
    }
}
