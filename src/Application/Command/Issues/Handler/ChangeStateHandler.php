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

namespace eTraxis\Application\Command\Issues\Handler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Issues\ChangeStateCommand;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\FieldValueRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
class ChangeStateHandler extends AbstractIssueHandler
{
    private StateRepositoryInterface $stateRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TranslatorInterface           $translator
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokenStorage
     * @param UserRepositoryInterface       $userRepository
     * @param IssueRepositoryInterface      $issueRepository
     * @param EventRepositoryInterface      $eventRepository
     * @param FieldValueRepositoryInterface $valueRepository
     * @param EntityManagerInterface        $manager
     * @param StateRepositoryInterface      $stateRepository
     */
    public function __construct(
        TranslatorInterface           $translator,
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TokenStorageInterface         $tokenStorage,
        UserRepositoryInterface       $userRepository,
        IssueRepositoryInterface      $issueRepository,
        EventRepositoryInterface      $eventRepository,
        FieldValueRepositoryInterface $valueRepository,
        EntityManagerInterface        $manager,
        StateRepositoryInterface      $stateRepository
    )
    {
        parent::__construct($translator, $security, $validator, $tokenStorage, $userRepository, $issueRepository, $eventRepository, $valueRepository, $manager);

        $this->stateRepository = $stateRepository;
    }

    /**
     * Command handler.
     *
     * @param ChangeStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(ChangeStateCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        /** @var null|\eTraxis\Entity\State $state */
        $state = $this->stateRepository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException('Unknown state.');
        }

        if (!$this->security->isGranted(IssueVoter::CHANGE_STATE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to change the current state.');
        }

        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if (!in_array($state, $this->issueRepository->getTransitionsByUser($issue, $user), true)) {
            throw new AccessDeniedHttpException('You are not allowed to change the current state to specified one.');
        }

        if (!$issue->isClosed && $state->isFinal) {
            $eventType = EventType::ISSUE_CLOSED;
        }
        elseif ($issue->isClosed && !$state->isFinal) {
            $eventType = EventType::ISSUE_REOPENED;
        }
        else {
            $eventType = EventType::STATE_CHANGED;
        }

        $issue->state = $state;

        $event = new Event($eventType, $issue, $user, $state->id);

        $this->issueRepository->persist($issue);
        $this->eventRepository->persist($event);

        $this->validateState($issue, $event, $command);
    }
}
