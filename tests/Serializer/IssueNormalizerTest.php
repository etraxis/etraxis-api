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
use eTraxis\Entity\Issue;
use eTraxis\Entity\LastRead;
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\IssueNormalizer
 */
class IssueNormalizerTest extends WebTestCase
{
    /**
     * @var IssueNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $security */
        $security = self::$container->get('security.authorization_checker');

        /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage */
        $tokenStorage = self::$container->get('security.token_storage');

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router = self::$container->get('router');

        /** @var \eTraxis\Repository\Contracts\IssueRepositoryInterface $issueRepository */
        $issueRepository = $this->doctrine->getRepository(Issue::class);

        /** @var \eTraxis\Repository\Contracts\LastReadRepositoryInterface $lastReadRepository */
        $lastReadRepository = $this->doctrine->getRepository(LastRead::class);

        $projectNormalizer  = new ProjectNormalizer($security, $router);
        $templateNormalizer = new TemplateNormalizer($security, $router, $projectNormalizer);
        $stateNormalizer    = new StateNormalizer($security, $router, $templateNormalizer);
        $this->normalizer   = new IssueNormalizer($security, $tokenStorage, $router, $issueRepository, $lastReadRepository, $stateNormalizer);
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSelfOnly()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var User $author */
        $author = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dtillman@example.com']);

        /** @var User $responsible */
        $responsible = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'cbatz@example.com']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'           => $issue->id,
            'subject'      => 'Support request 4',
            'created_at'   => $issue->createdAt,
            'changed_at'   => $issue->changedAt,
            'closed_at'    => null,
            'author'       => [
                'id'       => $author->id,
                'email'    => 'dtillman@example.com',
                'fullname' => 'Derrick Tillman',
            ],
            'state'        => [
                'id'          => $issue->state->id,
                'template'    => [
                    'id'          => $issue->state->template->id,
                    'project'     => [
                        'id'          => $issue->state->template->project->id,
                        'name'        => 'Excepturi',
                        'description' => 'Project C',
                        'created'     => $issue->state->template->project->createdAt,
                        'suspended'   => false,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/projects/%s', $baseUrl, $issue->state->template->project->id),
                                'type' => 'GET',
                            ],
                        ],
                    ],
                    'name'        => 'Support',
                    'prefix'      => 'req',
                    'description' => 'Support Request C',
                    'critical'    => 3,
                    'frozen'      => 7,
                    'locked'      => false,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/templates/%s', $baseUrl, $issue->state->template->id),
                            'type' => 'GET',
                        ],
                    ],
                ],
                'name'        => 'Opened',
                'type'        => 'intermediate',
                'responsible' => 'assign',
                'next'        => null,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/states/%s', $baseUrl, $issue->state->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'responsible'  => [
                'id'       => $responsible->id,
                'email'    => 'cbatz@example.com',
                'fullname' => 'Carter Batz',
            ],
            'is_cloned'    => false,
            'origin'       => null,
            'age'          => $issue->age,
            'is_critical'  => true,
            'is_suspended' => false,
            'resumes_at'   => null,
            'is_closed'    => false,
            'is_frozen'    => false,
            'read_at'      => null,
            'links'        => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/issues/%s', $baseUrl, $issue->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($issue, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinksGenericIssue()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var State $resolved */
        [/* skipping */, /* skipping */, $resolved] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        /** @var User $author */
        $author = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dtillman@example.com']);

        /** @var User $responsible */
        $responsible = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'cbatz@example.com']);

        /** @var User $kbahringer */
        $kbahringer = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'kbahringer@example.com']);

        /** @var User $tbuckridge */
        $tbuckridge = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tbuckridge@example.com']);

        /** @var User $tmarquardt */
        $tmarquardt = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'           => $issue->id,
            'subject'      => 'Support request 4',
            'created_at'   => $issue->createdAt,
            'changed_at'   => $issue->changedAt,
            'closed_at'    => null,
            'author'       => [
                'id'       => $author->id,
                'email'    => 'dtillman@example.com',
                'fullname' => 'Derrick Tillman',
            ],
            'state'        => [
                'id'          => $issue->state->id,
                'template'    => [
                    'id'          => $issue->state->template->id,
                    'project'     => [
                        'id'          => $issue->state->template->project->id,
                        'name'        => 'Excepturi',
                        'description' => 'Project C',
                        'created'     => $issue->state->template->project->createdAt,
                        'suspended'   => false,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/projects/%s', $baseUrl, $issue->state->template->project->id),
                                'type' => 'GET',
                            ],
                        ],
                    ],
                    'name'        => 'Support',
                    'prefix'      => 'req',
                    'description' => 'Support Request C',
                    'critical'    => 3,
                    'frozen'      => 7,
                    'locked'      => false,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/templates/%s', $baseUrl, $issue->state->template->id),
                            'type' => 'GET',
                        ],
                    ],
                ],
                'name'        => 'Opened',
                'type'        => 'intermediate',
                'responsible' => 'assign',
                'next'        => null,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/states/%s', $baseUrl, $issue->state->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'responsible'  => [
                'id'       => $responsible->id,
                'email'    => 'cbatz@example.com',
                'fullname' => 'Carter Batz',
            ],
            'is_cloned'    => false,
            'origin'       => null,
            'age'          => $issue->age,
            'is_critical'  => true,
            'is_suspended' => false,
            'resumes_at'   => null,
            'is_closed'    => false,
            'is_frozen'    => false,
            'read_at'      => null,
            'links'        => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/issues/%s', $baseUrl, $issue->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'issue.update',
                    'href' => sprintf('%s/api/issues/%s', $baseUrl, $issue->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'issue.delete',
                    'href' => sprintf('%s/api/issues/%s', $baseUrl, $issue->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'    => 'state.change',
                    'href'   => sprintf('%s/api/issues/%s/state/{state}', $baseUrl, $issue->id),
                    'type'   => 'POST',
                    'states' => [
                        [
                            'id'          => $resolved->id,
                            'name'        => $resolved->name,
                            'type'        => $resolved->type,
                            'responsible' => $resolved->responsible,
                        ],
                    ],
                ],
                [
                    'rel'   => 'issue.reassign',
                    'href'  => sprintf('%s/api/issues/%s/assign/{user}', $baseUrl, $issue->id),
                    'type'  => 'POST',
                    'users' => [
                        [
                            'id'       => $kbahringer->id,
                            'email'    => 'kbahringer@example.com',
                            'fullname' => 'Kailyn Bahringer',
                        ],
                        [
                            'id'       => $tbuckridge->id,
                            'email'    => 'tbuckridge@example.com',
                            'fullname' => 'Tony Buckridge',
                        ],
                        [
                            'id'       => $tmarquardt->id,
                            'email'    => 'tmarquardt@example.com',
                            'fullname' => 'Tracy Marquardt',
                        ],
                    ],
                ],
                [
                    'rel'  => 'issue.suspend',
                    'href' => sprintf('%s/api/issues/%s/suspend', $baseUrl, $issue->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'comment.public.add',
                    'href' => sprintf('%s/api/issues/%s/comments', $baseUrl, $issue->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'comment.private.add',
                    'href' => sprintf('%s/api/issues/%s/comments', $baseUrl, $issue->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'comment.private.read',
                    'href' => sprintf('%s/api/issues/%s/comments', $baseUrl, $issue->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'file.attach',
                    'href' => sprintf('%s/api/issues/%s/files', $baseUrl, $issue->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'dependency.add',
                    'href' => sprintf('%s/api/issues/%s/dependencies', $baseUrl, $issue->id),
                    'type' => 'PATCH',
                ],
                [
                    'rel'  => 'dependency.remove',
                    'href' => sprintf('%s/api/issues/%s/dependencies', $baseUrl, $issue->id),
                    'type' => 'PATCH',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($issue, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinksSuspendedIssue()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        /** @var User $author */
        $author = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'jmueller@example.com']);

        /** @var User $responsible */
        $responsible = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'cbatz@example.com']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'           => $issue->id,
            'subject'      => 'Support request 5',
            'created_at'   => $issue->createdAt,
            'changed_at'   => $issue->changedAt,
            'closed_at'    => null,
            'author'       => [
                'id'       => $author->id,
                'email'    => 'jmueller@example.com',
                'fullname' => 'Jeramy Mueller',
            ],
            'state'        => [
                'id'          => $issue->state->id,
                'template'    => [
                    'id'          => $issue->state->template->id,
                    'project'     => [
                        'id'          => $issue->state->template->project->id,
                        'name'        => 'Excepturi',
                        'description' => 'Project C',
                        'created'     => $issue->state->template->project->createdAt,
                        'suspended'   => false,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/projects/%s', $baseUrl, $issue->state->template->project->id),
                                'type' => 'GET',
                            ],
                        ],
                    ],
                    'name'        => 'Support',
                    'prefix'      => 'req',
                    'description' => 'Support Request C',
                    'critical'    => 3,
                    'frozen'      => 7,
                    'locked'      => false,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/templates/%s', $baseUrl, $issue->state->template->id),
                            'type' => 'GET',
                        ],
                    ],
                ],
                'name'        => 'Opened',
                'type'        => 'intermediate',
                'responsible' => 'assign',
                'next'        => null,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/states/%s', $baseUrl, $issue->state->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'responsible'  => [
                'id'       => $responsible->id,
                'email'    => 'cbatz@example.com',
                'fullname' => 'Carter Batz',
            ],
            'is_cloned'    => false,
            'origin'       => null,
            'age'          => $issue->age,
            'is_critical'  => true,
            'is_suspended' => true,
            'resumes_at'   => $issue->resumesAt,
            'is_closed'    => false,
            'is_frozen'    => false,
            'read_at'      => null,
            'links'        => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/issues/%s', $baseUrl, $issue->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'issue.resume',
                    'href' => sprintf('%s/api/issues/%s/resume', $baseUrl, $issue->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'comment.private.read',
                    'href' => sprintf('%s/api/issues/%s/comments', $baseUrl, $issue->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($issue, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $user  = new User();
        $issue = new Issue($user);

        self::assertTrue($this->normalizer->supportsNormalization($issue, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($issue, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization(new \stdClass(), 'json'));
    }
}
