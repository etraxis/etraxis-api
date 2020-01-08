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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Issues\AbstractIssueCommand;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Dictionary\StateResponsible;
use eTraxis\Entity\Event;
use eTraxis\Entity\Issue;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\FieldValueRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base command handler for issues.
 */
abstract class AbstractIssueHandler
{
    protected $translator;
    protected $security;
    protected $validator;
    protected $tokenStorage;
    protected $userRepository;
    protected $issueRepository;
    protected $eventRepository;
    protected $valueRepository;
    protected $manager;

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
        EntityManagerInterface        $manager
    )
    {
        $this->translator      = $translator;
        $this->security        = $security;
        $this->validator       = $validator;
        $this->tokenStorage    = $tokenStorage;
        $this->userRepository  = $userRepository;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
        $this->valueRepository = $valueRepository;
        $this->manager         = $manager;
    }

    /**
     * Validates and processes state fields of specified command.
     *
     * @param Issue                $issue   Target issue.
     * @param Event                $event   Current event.
     * @param AbstractIssueCommand $command Current command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     * @throws ValidationFailedException
     */
    protected function validateState(Issue $issue, Event $event, AbstractIssueCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        // Validate field values.
        $defaults    = [];
        $constraints = [];

        foreach ($issue->state->fields as $field) {
            $defaults[$field->id]    = null;
            $constraints[$field->id] = $field->getFacade($this->manager)->getValidationConstraints($this->translator);
        }

        $command->fields = $command->fields + $defaults;

        /** @var \Symfony\Component\Validator\Mapping\ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor($command);

        if ($issue->state->responsible === StateResponsible::ASSIGN) {
            $metadata->addPropertyConstraint('responsible', new Assert\NotBlank());
        }

        $metadata->addPropertyConstraint('fields', new Assert\Collection([
            'fields'             => $constraints,
            'allowExtraFields'   => false,
            'allowMissingFields' => false,
        ]));

        $errors = $this->validator->validate($command);

        if (count($errors)) {
            throw new ValidationFailedException($command, $errors);
        }

        // Set field values.
        foreach ($issue->state->fields as $field) {
            $this->valueRepository->setFieldValue($issue, $event, $field, $command->fields[$field->id]);
        }

        // Whether the issue must be assigned.
        if ($issue->state->responsible === StateResponsible::ASSIGN) {

            $issue->responsible = $this->userRepository->find($command->responsible);

            if (!$issue->responsible) {
                throw new NotFoundHttpException('Unknown user.');
            }

            $responsibles = $this->issueRepository->getResponsiblesByUser($issue, $user);

            if (!in_array($issue->responsible, $responsibles, true)) {
                throw new AccessDeniedHttpException('The issue cannot be assigned to specified user.');
            }

            $event2 = new Event(EventType::ISSUE_ASSIGNED, $issue, $user, $issue->responsible->id);

            $this->issueRepository->persist($issue);
            $this->eventRepository->persist($event2);
        }
        elseif ($issue->state->responsible === StateResponsible::REMOVE) {

            $issue->responsible = null;

            $this->issueRepository->persist($issue);
        }
    }
}
