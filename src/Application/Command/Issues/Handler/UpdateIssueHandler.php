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
use eTraxis\Application\Command\Issues\UpdateIssueCommand;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\FieldValueRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
class UpdateIssueHandler
{
    private $translator;
    private $security;
    private $validator;
    private $tokenStorage;
    private $issueRepository;
    private $eventRepository;
    private $valueRepository;
    private $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TranslatorInterface           $translator
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokenStorage
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
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
        $this->valueRepository = $valueRepository;
        $this->manager         = $manager;
    }

    /**
     * Command handler.
     *
     * @param UpdateIssueCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     * @throws ValidationFailedException
     */
    public function __invoke(UpdateIssueCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this issue.');
        }

        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $event = new Event(EventType::ISSUE_EDITED, $issue, $user);

        $this->eventRepository->persist($event);

        if (mb_strlen($command->subject) !== 0) {
            $this->issueRepository->changeSubject($issue, $event, $command->subject);
        }

        // Validate field values.
        $defaults    = [];
        $constraints = [];

        foreach ($issue->values as $fieldValue) {
            $field = $fieldValue->field;

            $defaults[$field->id]    = $this->valueRepository->getFieldValue($fieldValue, $user);
            $constraints[$field->id] = $field->getFacade($this->manager)->getValidationConstraints($this->translator, $fieldValue->createdAt);
        }

        $command->fields = $command->fields + $defaults;

        /** @var \Symfony\Component\Validator\Mapping\ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor(UpdateIssueCommand::class);

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
        foreach ($issue->values as $fieldValue) {
            $this->valueRepository->setFieldValue($issue, $event, $fieldValue->field, $command->fields[$fieldValue->field->id]);
        }
    }
}
