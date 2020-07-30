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

use eTraxis\Application\Command\Issues\AddCommentCommand;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Comment;
use eTraxis\Entity\Event;
use eTraxis\Repository\Contracts\CommentRepositoryInterface;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class AddCommentHandler
{
    private AuthorizationCheckerInterface $security;
    private TokenStorageInterface         $tokenStorage;
    private IssueRepositoryInterface      $issueRepository;
    private EventRepositoryInterface      $eventRepository;
    private CommentRepositoryInterface    $commentRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokenStorage
     * @param IssueRepositoryInterface      $issueRepository
     * @param EventRepositoryInterface      $eventRepository
     * @param CommentRepositoryInterface    $commentRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokenStorage,
        IssueRepositoryInterface      $issueRepository,
        EventRepositoryInterface      $eventRepository,
        CommentRepositoryInterface    $commentRepository
    )
    {
        $this->security          = $security;
        $this->tokenStorage      = $tokenStorage;
        $this->issueRepository   = $issueRepository;
        $this->eventRepository   = $eventRepository;
        $this->commentRepository = $commentRepository;
    }

    /**
     * Command handler.
     *
     * @param AddCommentCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(AddCommentCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\eTraxis\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if ($command->private) {
            if (!$this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issue)) {
                throw new AccessDeniedHttpException('You are not allowed to comment this issue privately.');
            }
        }
        else {
            if (!$this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issue)) {
                throw new AccessDeniedHttpException('You are not allowed to comment this issue.');
            }
        }

        $event = new Event(
            $command->private ? EventType::PRIVATE_COMMENT : EventType::PUBLIC_COMMENT,
            $issue,
            $user
        );

        $comment = new Comment($event);

        $comment->body      = $command->body;
        $comment->isPrivate = $command->private;

        $this->eventRepository->persist($event);
        $this->commentRepository->persist($comment);
    }
}
