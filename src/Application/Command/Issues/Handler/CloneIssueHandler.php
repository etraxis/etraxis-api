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
use eTraxis\Application\Command\Issues\CloneIssueCommand;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Entity\Issue;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\FieldValueRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
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
class CloneIssueHandler extends AbstractIssueHandler
{
    private TemplateRepositoryInterface $templateRepository;

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
     * @param TemplateRepositoryInterface   $templateRepository
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
        TemplateRepositoryInterface   $templateRepository
    )
    {
        parent::__construct($translator, $security, $validator, $tokenStorage, $userRepository, $issueRepository, $eventRepository, $valueRepository, $manager);

        $this->templateRepository = $templateRepository;
    }

    /**
     * Command handler.
     *
     * @param CloneIssueCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return Issue
     */
    public function __invoke(CloneIssueCommand $command): Issue
    {
        /** @var null|Issue $origin */
        $origin = $this->issueRepository->find($command->issue);

        if (!$origin) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::CREATE_ISSUE, $origin->template)) {
            throw new AccessDeniedHttpException('You are not allowed to create new issue.');
        }

        /** @var \eTraxis\Entity\User $author */
        $author = $this->tokenStorage->getToken()->getUser();

        $issue = new Issue($author, $origin);

        $issue->state   = $origin->template->initialState;
        $issue->subject = $command->subject;

        $event = new Event(EventType::ISSUE_CREATED, $issue, $author, $issue->state->id);

        $this->issueRepository->persist($issue);
        $this->eventRepository->persist($event);

        $this->validateState($issue, $event, $command);

        return $issue;
    }
}
