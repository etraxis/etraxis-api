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

namespace eTraxis\Application\Command\Issues\Handler;

use eTraxis\Application\Command\Issues\ReassignIssueCommand;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class ReassignIssueHandler
{
    private $security;
    private $tokens;
    private $userRepository;
    private $issueRepository;
    private $eventRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokens
     * @param UserRepositoryInterface       $userRepository
     * @param IssueRepositoryInterface      $issueRepository
     * @param EventRepositoryInterface      $eventRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokens,
        UserRepositoryInterface       $userRepository,
        IssueRepositoryInterface      $issueRepository,
        EventRepositoryInterface      $eventRepository
    )
    {
        $this->security        = $security;
        $this->tokens          = $tokens;
        $this->userRepository  = $userRepository;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Command handler.
     *
     * @param ReassignIssueCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(ReassignIssueCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        /** @var null|\eTraxis\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        $responsible = $this->userRepository->find($command->responsible);

        if (!$responsible) {
            throw new NotFoundHttpException('Unknown user.');
        }

        if (!$this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to reassign this issue.');
        }

        $responsibles = $this->issueRepository->getResponsiblesByUser($issue, $user, true);

        if (!in_array($responsible, $responsibles, true)) {
            throw new AccessDeniedHttpException('The issue cannot be assigned to specified user.');
        }

        if ($issue->responsible !== $responsible) {

            $issue->responsible = $responsible;

            $event = new Event(EventType::ISSUE_ASSIGNED, $issue, $user, $responsible->id);

            $this->issueRepository->persist($issue);
            $this->eventRepository->persist($event);
        }
    }
}
