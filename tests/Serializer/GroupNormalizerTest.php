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
use eTraxis\Entity\Group;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\GroupNormalizer
 */
class GroupNormalizerTest extends WebTestCase
{
    /**
     * @var GroupNormalizer
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
        $this->normalizer  = new GroupNormalizer($security, $router, $projectNormalizer);
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeLocalSelfOnly()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'DESC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = str_replace('/api/groups', null, rtrim($router->generate('api_groups_list', [], UrlGeneratorInterface::ABSOLUTE_URL), '/'));

        $expected = [
            'id'          => $group->id,
            'project'     => [
                'id'          => $group->project->id,
                'name'        => 'Presto',
                'description' => 'Project D',
                'created'     => $group->project->createdAt,
                'suspended'   => false,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/projects/%s', $baseUrl, $group->project->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'name'        => 'Managers',
            'description' => 'Managers D',
            'global'      => false,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/groups/%s', $baseUrl, $group->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($group, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeGlobalSelfOnly()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        $group = $this->doctrine->getRepository(Group::class)->findOneBy(['name' => 'Company Clients']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = str_replace('/api/groups', null, rtrim($router->generate('api_groups_list', [], UrlGeneratorInterface::ABSOLUTE_URL), '/'));

        $expected = [
            'id'          => $group->id,
            'project'     => null,
            'name'        => 'Company Clients',
            'description' => null,
            'global'      => true,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/groups/%s', $baseUrl, $group->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($group, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinks()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'DESC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = str_replace('/api/groups', null, rtrim($router->generate('api_groups_list', [], UrlGeneratorInterface::ABSOLUTE_URL), '/'));

        $expected = [
            'id'          => $group->id,
            'project'     => [
                'id'          => $group->project->id,
                'name'        => 'Presto',
                'description' => 'Project D',
                'created'     => $group->project->createdAt,
                'suspended'   => false,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/projects/%s', $baseUrl, $group->project->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'name'        => 'Managers',
            'description' => 'Managers D',
            'global'      => false,
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/groups/%s', $baseUrl, $group->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'update',
                    'href' => sprintf('%s/api/groups/%s', $baseUrl, $group->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'delete',
                    'href' => sprintf('%s/api/groups/%s', $baseUrl, $group->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'  => 'members',
                    'href' => sprintf('%s/api/groups/%s/members', $baseUrl, $group->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'add_members',
                    'href' => sprintf('%s/api/groups/%s/members', $baseUrl, $group->id),
                    'type' => 'PATCH',
                ],
                [
                    'rel'  => 'remove_members',
                    'href' => sprintf('%s/api/groups/%s/members', $baseUrl, $group->id),
                    'type' => 'PATCH',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($group, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $group = new Group();
        $user  = new User();

        self::assertTrue($this->normalizer->supportsNormalization($group, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($group, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization($user, 'json'));
    }
}
