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

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Entity\File;
use eTraxis\Entity\Issue;
use eTraxis\Entity\LastRead;
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\EventNormalizer
 */
class EventNormalizerTest extends WebTestCase
{
    private NormalizerInterface $normalizer;

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
        $issueNormalizer    = new IssueNormalizer($security, $tokenStorage, $router, $issueRepository, $lastReadRepository, $stateNormalizer);
        $fileNormalizer     = new FileNormalizer($security, $router);

        /** @var \eTraxis\Repository\Contracts\StateRepositoryInterface $stateRepository */
        /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface $userRepository */
        /** @var \eTraxis\Repository\Contracts\FileRepositoryInterface $fileRepository */
        $stateRepository = $this->doctrine->getRepository(State::class);
        $userRepository  = $this->doctrine->getRepository(User::class);
        $fileRepository  = $this->doctrine->getRepository(File::class);

        $this->normalizer = new EventNormalizer(
            $issueNormalizer,
            $fileNormalizer,
            $stateRepository,
            $userRepository,
            $fileRepository,
            $issueRepository
        );
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeIssueEdited()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Event $event */
        [$event] = $this->doctrine->getRepository(Event::class)->findBy([
            'type'  => EventType::ISSUE_EDITED,
            'issue' => $issue,
        ], [
            'createdAt' => 'ASC',
        ]);

        $expected = [
            'type'      => 'issue.edited',
            'user'      => [
                'id'       => $event->user->id,
                'email'    => 'ldoyle@example.com',
                'fullname' => 'Leland Doyle',
            ],
            'timestamp' => $event->createdAt,
        ];

        static::assertSame($expected, $this->normalizer->normalize($event, 'json'));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeStateChanged()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Event $event */
        [$event] = $this->doctrine->getRepository(Event::class)->findBy([
            'type'  => EventType::STATE_CHANGED,
            'issue' => $issue,
        ], [
            'createdAt' => 'ASC',
        ]);

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $expected = [
            'type'      => 'state.changed',
            'user'      => [
                'id'       => $event->user->id,
                'email'    => 'ldoyle@example.com',
                'fullname' => 'Leland Doyle',
            ],
            'timestamp' => $event->createdAt,
            'state'     => [
                'id'          => $state->id,
                'name'        => 'Assigned',
                'type'        => 'intermediate',
                'responsible' => 'assign',
            ],
        ];

        static::assertSame($expected, $this->normalizer->normalize($event, 'json'));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeIssueAssigned()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Event $event */
        [$event] = $this->doctrine->getRepository(Event::class)->findBy([
            'type'  => EventType::ISSUE_ASSIGNED,
            'issue' => $issue,
        ], [
            'createdAt' => 'ASC',
        ]);

        /** @var \Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->loadUserByUsername('fdooley@example.com');

        $expected = [
            'type'      => 'issue.assigned',
            'user'      => [
                'id'       => $event->user->id,
                'email'    => 'ldoyle@example.com',
                'fullname' => 'Leland Doyle',
            ],
            'timestamp' => $event->createdAt,
            'assignee'  => [
                'id'       => $user->id,
                'email'    => 'fdooley@example.com',
                'fullname' => 'Francesca Dooley',
            ],
        ];

        static::assertSame($expected, $this->normalizer->normalize($event, 'json'));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeFileAttached()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Event $event */
        [$event] = $this->doctrine->getRepository(Event::class)->findBy([
            'type'  => EventType::FILE_ATTACHED,
            'issue' => $issue,
        ], [
            'createdAt' => 'ASC',
        ]);

        /** @var File $file */
        [$file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'type'      => 'file.attached',
            'user'      => [
                'id'       => $event->user->id,
                'email'    => 'ldoyle@example.com',
                'fullname' => 'Leland Doyle',
            ],
            'timestamp' => $event->createdAt,
            'file'      => [
                'id'        => $file->id,
                'user'      => [
                    'id'       => $event->user->id,
                    'email'    => 'ldoyle@example.com',
                    'fullname' => 'Leland Doyle',
                ],
                'timestamp' => $file->event->createdAt,
                'name'      => 'Inventore.pdf',
                'size'      => 175971,
                'type'      => 'application/pdf',
                'links'     => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/files/%s', $baseUrl, $file->id),
                        'type' => 'GET',
                    ],
                ],
            ],
        ];

        static::assertSame($expected, $this->normalizer->normalize($event, 'json'));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeDependencyAdded()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Event $event */
        [$event] = $this->doctrine->getRepository(Event::class)->findBy([
            'type'  => EventType::DEPENDENCY_ADDED,
            'issue' => $issue,
        ], [
            'createdAt' => 'ASC',
        ]);

        /** @var Issue $dependency */
        [$dependency] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'type'      => 'dependency.added',
            'user'      => [
                'id'       => $event->user->id,
                'email'    => 'ldoyle@example.com',
                'fullname' => 'Leland Doyle',
            ],
            'timestamp' => $event->createdAt,
            'issue'     => [
                'id'           => $dependency->id,
                'subject'      => 'Development task 2',
                'created_at'   => $dependency->createdAt,
                'changed_at'   => $dependency->changedAt,
                'closed_at'    => null,
                'author'       => [
                    'id'       => $dependency->author->id,
                    'email'    => 'dorcas.ernser@example.com',
                    'fullname' => 'Dorcas Ernser',
                ],
                'state'        => [
                    'id'          => $dependency->state->id,
                    'template'    => [
                        'id'          => $dependency->state->template->id,
                        'project'     => [
                            'id'          => $dependency->state->template->project->id,
                            'name'        => 'Distinctio',
                            'description' => 'Project A',
                            'created'     => $dependency->state->template->project->createdAt,
                            'suspended'   => true,
                            'links'       => [
                                [
                                    'rel'  => 'self',
                                    'href' => sprintf('%s/api/projects/%s', $baseUrl, $dependency->state->template->project->id),
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
                                'href' => sprintf('%s/api/templates/%s', $baseUrl, $dependency->state->template->id),
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
                            'href' => sprintf('%s/api/states/%s', $baseUrl, $dependency->state->id),
                            'type' => 'GET',
                        ],
                    ],
                ],
                'responsible'  => [
                    'id'       => $dependency->responsible->id,
                    'email'    => 'dquigley@example.com',
                    'fullname' => 'Dennis Quigley',
                ],
                'is_cloned'    => false,
                'origin'       => null,
                'age'          => $dependency->age,
                'is_critical'  => false,
                'is_suspended' => false,
                'resumes_at'   => null,
                'is_closed'    => false,
                'is_frozen'    => false,
                'read_at'      => null,
                'links'        => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/issues/%s', $baseUrl, $dependency->id),
                        'type' => 'GET',
                    ],
                ],
            ],
        ];

        static::assertSame($expected, $this->normalizer->normalize($event, 'json'));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $user  = new User();
        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_EDITED, $issue, $user);

        static::assertTrue($this->normalizer->supportsNormalization($event, 'json'));
        static::assertFalse($this->normalizer->supportsNormalization($event, 'xml'));
        static::assertFalse($this->normalizer->supportsNormalization($issue, 'json'));
    }
}
