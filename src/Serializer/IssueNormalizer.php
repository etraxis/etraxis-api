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
use eTraxis\Entity\Issue;
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Repository\Contracts\LastReadRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'Issue' entity.
 */
class IssueNormalizer implements NormalizerInterface
{
    // HATEOAS links.
    public const CLONE_ISSUE         = 'clone';
    public const UPDATE_ISSUE        = 'update';
    public const DELETE_ISSUE        = 'delete';
    public const CHANGE_STATE        = 'change_state';
    public const REASSIGN_ISSUE      = 'reassign';
    public const SUSPEND_ISSUE       = 'suspend';
    public const RESUME_ISSUE        = 'resume';
    public const READ_ISSUE          = 'read';
    public const UNREAD_ISSUE        = 'unread';
    public const LIST_EVENTS         = 'events';
    public const LIST_CHANGES        = 'changes';
    public const LIST_WATCHERS       = 'watchers';
    public const WATCH_ISSUE         = 'watch';
    public const UNWATCH_ISSUE       = 'unwatch';
    public const LIST_COMMENTS       = 'comments';
    public const ADD_PUBLIC_COMMENT  = 'add_public_comment';
    public const ADD_PRIVATE_COMMENT = 'add_private_comment';
    public const LIST_FILES          = 'files';
    public const ATTACH_FILE         = 'attach_file';
    public const LIST_DEPENDENCIES   = 'dependencies';
    public const ADD_DEPENDENCY      = 'add_dependency';
    public const REMOVE_DEPENDENCY   = 'remove_dependency';

    private AuthorizationCheckerInterface $security;
    private TokenStorageInterface         $tokenStorage;
    private RouterInterface               $router;
    private IssueRepositoryInterface      $issueRepository;
    private LastReadRepositoryInterface   $lastReadRepository;
    private StateNormalizer               $stateNormalizer;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokenStorage
     * @param RouterInterface               $router
     * @param IssueRepositoryInterface      $issueRepository
     * @param LastReadRepositoryInterface   $lastReadRepository
     * @param StateNormalizer               $stateNormalizer
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokenStorage,
        RouterInterface               $router,
        IssueRepositoryInterface      $issueRepository,
        LastReadRepositoryInterface   $lastReadRepository,
        StateNormalizer               $stateNormalizer
    )
    {
        $this->security           = $security;
        $this->tokenStorage       = $tokenStorage;
        $this->router             = $router;
        $this->issueRepository    = $issueRepository;
        $this->lastReadRepository = $lastReadRepository;
        $this->stateNormalizer    = $stateNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var Issue $object */
        $url = $this->router->generate('api_issues_get', [
            'id' => $object->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        /** @var \eTraxis\Entity\LastRead $lastRead */
        $lastRead = $this->lastReadRepository->findLastRead($object);

        $result = [
            Issue::JSON_ID           => $object->id,
            Issue::JSON_SUBJECT      => $object->subject,
            Issue::JSON_CREATED_AT   => $object->createdAt,
            Issue::JSON_CHANGED_AT   => $object->changedAt,
            Issue::JSON_CLOSED_AT    => $object->closedAt,
            Issue::JSON_AUTHOR       => [
                User::JSON_ID       => $object->author->id,
                User::JSON_EMAIL    => $object->author->email,
                User::JSON_FULLNAME => $object->author->fullname,
            ],
            Issue::JSON_STATE        => $this->stateNormalizer->normalize($object->state, $format, [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
            Issue::JSON_RESPONSIBLE  => $object->responsible === null
                ? null
                : [
                    User::JSON_ID       => $object->responsible->id,
                    User::JSON_EMAIL    => $object->responsible->email,
                    User::JSON_FULLNAME => $object->responsible->fullname,
                ],
            Issue::JSON_IS_CLONED    => $object->isCloned,
            Issue::JSON_ORIGIN       => $object->origin === null ? null : $object->origin->id,
            Issue::JSON_AGE          => $object->age,
            Issue::JSON_IS_CRITICAL  => $object->isCritical,
            Issue::JSON_IS_SUSPENDED => $object->isSuspended,
            Issue::JSON_RESUMES_AT   => $object->resumesAt,
            Issue::JSON_IS_CLOSED    => $object->isClosed,
            Issue::JSON_IS_FROZEN    => $object->isFrozen,
            Issue::JSON_READ_AT      => $lastRead === null ? null : $lastRead->readAt,
            Hateoas::LINKS           => [
                [
                    Hateoas::LINK_RELATION => Hateoas::SELF,
                    Hateoas::LINK_HREF     => $url,
                    Hateoas::LINK_TYPE     => Request::METHOD_GET,
                ],
            ],
        ];

        $mode = $context[Hateoas::MODE] ?? Hateoas::MODE_ALL_LINKS;

        if ($mode === Hateoas::MODE_SELF_ONLY) {
            return $result;
        }

        $links = [
            self::CLONE_ISSUE         => [
                $this->security->isGranted(IssueVoter::CREATE_ISSUE, $object->template),
                $this->router->generate('api_issues_clone', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::UPDATE_ISSUE        => [
                $this->security->isGranted(IssueVoter::UPDATE_ISSUE, $object),
                $this->router->generate('api_issues_update', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::DELETE_ISSUE        => [
                $this->security->isGranted(IssueVoter::DELETE_ISSUE, $object),
                $this->router->generate('api_issues_delete', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_DELETE,
            ],
            self::CHANGE_STATE        => [
                $this->security->isGranted(IssueVoter::CHANGE_STATE, $object),
                null,
                Request::METHOD_POST,
            ],
            self::REASSIGN_ISSUE      => [
                $this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $object),
                null,
                Request::METHOD_POST,
            ],
            self::SUSPEND_ISSUE       => [
                $this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $object),
                $this->router->generate('api_issues_suspend', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::RESUME_ISSUE        => [
                $this->security->isGranted(IssueVoter::RESUME_ISSUE, $object),
                $this->router->generate('api_issues_resume', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::READ_ISSUE          => [
                true,
                $this->router->generate('api_issues_read', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::UNREAD_ISSUE        => [
                true,
                $this->router->generate('api_issues_unread', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::LIST_EVENTS         => [
                true,
                $this->router->generate('api_issues_events', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::LIST_CHANGES        => [
                true,
                $this->router->generate('api_issues_changes', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::LIST_WATCHERS       => [
                true,
                $this->router->generate('api_issues_watchers', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::WATCH_ISSUE         => [
                true,
                $this->router->generate('api_issues_watch', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::UNWATCH_ISSUE       => [
                true,
                $this->router->generate('api_issues_unwatch', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::LIST_COMMENTS       => [
                true,
                $this->router->generate('api_issues_comments', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::ADD_PUBLIC_COMMENT  => [
                $this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $object),
                $this->router->generate('api_issues_comments_create', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::ADD_PRIVATE_COMMENT => [
                $this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $object),
                $this->router->generate('api_issues_comments_create', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::LIST_FILES          => [
                true,
                $this->router->generate('api_files_list', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::ATTACH_FILE         => [
                $this->security->isGranted(IssueVoter::ATTACH_FILE, $object),
                $this->router->generate('api_files_create', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::LIST_DEPENDENCIES   => [
                true,
                $this->router->generate('api_issues_dependencies_get', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::ADD_DEPENDENCY      => [
                $this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $object),
                $this->router->generate('api_issues_dependencies_set', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PATCH,
            ],
            self::REMOVE_DEPENDENCY   => [
                $this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $object),
                $this->router->generate('api_issues_dependencies_set', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PATCH,
            ],
        ];

        foreach ($links as $relation => $link) {
            if ($link[0]) {
                $result[Hateoas::LINKS][] = [
                    Hateoas::LINK_RELATION => $relation,
                    Hateoas::LINK_HREF     => $link[1],
                    Hateoas::LINK_TYPE     => $link[2],
                ];
            }
        }

        // Post-update for some of the generated links.
        array_walk($result[Hateoas::LINKS], function (&$entry) use ($object) {

            // Add available states.
            if ($entry[Hateoas::LINK_RELATION] === self::CHANGE_STATE) {

                /** @var User $user */
                $user = $this->tokenStorage->getToken()->getUser();

                $url = $this->router->generate('api_issues_state', [
                    'id'    => $object->id,
                    'state' => 0,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $entry[Hateoas::LINK_HREF] = mb_substr($url, 0, -1) . '{state}';

                $entry['states'] = array_map(fn (State $state) => [
                    'id'          => $state->id,
                    'name'        => $state->name,
                    'type'        => $state->type,
                    'responsible' => $state->responsible,
                ], $this->issueRepository->getTransitionsByUser($object, $user));
            }

            // Add available assignees.
            if ($entry[Hateoas::LINK_RELATION] === self::REASSIGN_ISSUE) {

                /** @var User $user */
                $user = $this->tokenStorage->getToken()->getUser();

                $url = $this->router->generate('api_issues_assign', [
                    'id'   => $object->id,
                    'user' => 0,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $entry[Hateoas::LINK_HREF] = mb_substr($url, 0, -1) . '{user}';

                $entry['users'] = array_map(fn (User $user) => [
                    'id'       => $user->id,
                    'email'    => $user->email,
                    'fullname' => $user->fullname,
                ], $this->issueRepository->getResponsiblesByUser($object, $user, true));
            }
        });

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof Issue;
    }
}
