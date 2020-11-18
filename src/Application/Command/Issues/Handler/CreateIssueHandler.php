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
use eTraxis\Application\Command\Issues\CreateIssueCommand;
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
class CreateIssueHandler extends AbstractIssueHandler
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
     * @param CreateIssueCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return Issue
     */
    public function __invoke(CreateIssueCommand $command): Issue
    {
        /** @var null|\eTraxis\Entity\Template $template */
        $template = $this->templateRepository->find($command->template);

        if (!$template) {
            throw new NotFoundHttpException('Unknown template.');
        }

        if (!$this->security->isGranted(IssueVoter::CREATE_ISSUE, $template)) {
            throw new AccessDeniedHttpException('You are not allowed to create new issue.');
        }

        /** @var \eTraxis\Entity\User $author */
        $author = $this->tokenStorage->getToken()->getUser();

        $issue = new Issue($author);

        $issue->state   = $template->initialState;
        $issue->subject = $command->subject;

        $event = new Event(EventType::ISSUE_CREATED, $issue, $author, $issue->state->id);

        $this->issueRepository->persist($issue);
        $this->eventRepository->persist($event);

        $this->validateState($issue, $event, $command);

        return $issue;
    }
}
