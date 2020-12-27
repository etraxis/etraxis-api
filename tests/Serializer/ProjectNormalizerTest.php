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

use eTraxis\Application\Hateoas;
use eTraxis\Entity\Group;
use eTraxis\Entity\Project;
use eTraxis\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\ProjectNormalizer
 */
class ProjectNormalizerTest extends WebTestCase
{
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $security */
        $security = self::$container->get('security.authorization_checker');

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router = self::$container->get('router');

        $this->normalizer = new ProjectNormalizer($security, $router);
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSelfOnly()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Presto']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $project->id,
            'name'        => 'Presto',
            'description' => 'Project D',
            'created'     => $project->createdAt,
            'suspended'   => false,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/projects/%s', $baseUrl, $project->id),
                    'type' => 'GET',
                ],
            ],
        ];

        static::assertSame($expected, $this->normalizer->normalize($project, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinks()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Presto']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $project->id,
            'name'        => 'Presto',
            'description' => 'Project D',
            'created'     => $project->createdAt,
            'suspended'   => false,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/projects/%s', $baseUrl, $project->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'update',
                    'href' => sprintf('%s/api/projects/%s', $baseUrl, $project->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'delete',
                    'href' => sprintf('%s/api/projects/%s', $baseUrl, $project->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'  => 'suspend',
                    'href' => sprintf('%s/api/projects/%s/suspend', $baseUrl, $project->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'create_template',
                    'href' => sprintf('%s/api/templates', $baseUrl),
                    'type' => 'POST',
                ],
            ],
        ];

        static::assertSame($expected, $this->normalizer->normalize($project, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $project = new Project();
        $group   = new Group();

        static::assertTrue($this->normalizer->supportsNormalization($project, 'json'));
        static::assertFalse($this->normalizer->supportsNormalization($project, 'xml'));
        static::assertFalse($this->normalizer->supportsNormalization($group, 'json'));
    }
}
