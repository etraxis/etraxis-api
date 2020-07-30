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

namespace eTraxis\Application\Command\Fields\Handler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Fields\AbstractCreateFieldCommand;
use eTraxis\Application\Command\Fields\AbstractFieldCommand;
use eTraxis\Entity\Field;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Voter\FieldVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract "Create field" command handler.
 */
abstract class AbstractCreateFieldHandler
{
    private AuthorizationCheckerInterface $security;
    private TranslatorInterface           $translator;
    private ValidatorInterface            $validator;
    private StateRepositoryInterface      $stateRepository;
    private FieldRepositoryInterface      $fieldRepository;
    private EntityManagerInterface        $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TranslatorInterface           $translator
     * @param ValidatorInterface            $validator
     * @param StateRepositoryInterface      $stateRepository
     * @param FieldRepositoryInterface      $fieldRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TranslatorInterface           $translator,
        ValidatorInterface            $validator,
        StateRepositoryInterface      $stateRepository,
        FieldRepositoryInterface      $fieldRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->security        = $security;
        $this->translator      = $translator;
        $this->validator       = $validator;
        $this->stateRepository = $stateRepository;
        $this->fieldRepository = $fieldRepository;
        $this->manager         = $manager;
    }

    /**
     * Returns field type supported by this command handler.
     *
     * @return string
     */
    abstract protected function getSupportedFieldType(): string;

    /**
     * Copies field-specific parameters from create/update command to specified field.
     *
     * @param TranslatorInterface    $translator
     * @param EntityManagerInterface $manager
     * @param AbstractFieldCommand   $command
     * @param Field                  $field
     *
     * @return Field Updated field entity.
     */
    abstract protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field;

    /**
     * Command handler.
     *
     * @param AbstractCreateFieldCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return Field
     */
    protected function create(AbstractCreateFieldCommand $command): Field
    {
        /** @var null|\eTraxis\Entity\State $state */
        $state = $this->stateRepository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(FieldVoter::CREATE_FIELD, $state)) {
            throw new AccessDeniedHttpException();
        }

        $field = new Field($state, $this->getSupportedFieldType());

        $field->name        = $command->name;
        $field->description = $command->description;
        $field->isRequired  = $command->required;
        $field->position    = count($state->fields) + 1;

        $field = $this->copyCommandToField($this->translator, $this->manager, $command, $field);

        $errors = $this->validator->validate($field);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->fieldRepository->persist($field);

        return $field;
    }
}
