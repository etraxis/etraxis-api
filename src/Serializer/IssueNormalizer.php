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
    private $security;
    private $tokenStorage;
    private $router;
    private $issueRepository;
    private $lastReadRepository;
    private $stateNormalizer;

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
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

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

        if ($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $object)) {

            $url = $this->router->generate('api_issues_update', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::UPDATE_ISSUE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PUT,
            ];
        }

        if ($this->security->isGranted(IssueVoter::DELETE_ISSUE, $object)) {

            $url = $this->router->generate('api_issues_delete', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::DELETE_ISSUE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_DELETE,
            ];
        }

        if ($this->security->isGranted(IssueVoter::CHANGE_STATE, $object)) {

            $url = $this->router->generate('api_issues_state', [
                'id'    => $object->id,
                'state' => 0,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $url = mb_substr($url, 0, -1) . '{state}';

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::CHANGE_STATE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
                'states'               => array_map(function (State $state) {
                    return [
                        'id'          => $state->id,
                        'name'        => $state->name,
                        'type'        => $state->type,
                        'responsible' => $state->responsible,
                    ];
                }, $this->issueRepository->getTransitionsByUser($object, $user)),
            ];
        }

        if ($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $object)) {

            $url = $this->router->generate('api_issues_assign', [
                'id'   => $object->id,
                'user' => 0,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $url = mb_substr($url, 0, -1) . '{user}';

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::REASSIGN_ISSUE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
                'users'                => array_map(function (User $user) {
                    return [
                        'id'       => $user->id,
                        'email'    => $user->email,
                        'fullname' => $user->fullname,
                    ];
                }, $this->issueRepository->getResponsiblesByUser($object, $user, true)),
            ];
        }

        if ($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $object)) {

            $url = $this->router->generate('api_issues_suspend', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::SUSPEND_ISSUE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(IssueVoter::RESUME_ISSUE, $object)) {

            $url = $this->router->generate('api_issues_resume', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::RESUME_ISSUE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $object)) {

            $url = $this->router->generate('api_issues_comments_create', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::ADD_PUBLIC_COMMENT,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $object)) {

            $url = $this->router->generate('api_issues_comments_create', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::ADD_PRIVATE_COMMENT,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $object)) {

            $url = $this->router->generate('api_issues_comments_list', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::READ_PRIVATE_COMMENT,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_GET,
            ];
        }

        if ($this->security->isGranted(IssueVoter::ATTACH_FILE, $object)) {

            $url = $this->router->generate('api_files_create', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::ATTACH_FILE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $object)) {

            $url = $this->router->generate('api_issues_dependencies_set', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::ADD_DEPENDENCY,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PATCH,
            ];
        }

        if ($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $object)) {

            $url = $this->router->generate('api_issues_dependencies_set', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::REMOVE_DEPENDENCY,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PATCH,
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof Issue;
    }
}
