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
use eTraxis\Application\Command\Fields\AbstractFieldCommand;
use eTraxis\Application\Command\Fields\AbstractUpdateFieldCommand;
use eTraxis\Entity\Field;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\Voter\FieldVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract "Update field" command handler.
 */
abstract class AbstractUpdateFieldHandler
{
    private AuthorizationCheckerInterface $security;
    private TranslatorInterface           $translator;
    private ValidatorInterface            $validator;
    private FieldRepositoryInterface      $repository;
    private EntityManagerInterface        $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TranslatorInterface           $translator
     * @param ValidatorInterface            $validator
     * @param FieldRepositoryInterface      $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TranslatorInterface           $translator,
        ValidatorInterface            $validator,
        FieldRepositoryInterface      $repository,
        EntityManagerInterface        $manager
    )
    {
        $this->security   = $security;
        $this->translator = $translator;
        $this->validator  = $validator;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

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
     * @param AbstractUpdateFieldCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    protected function update(AbstractUpdateFieldCommand $command): void
    {
        /** @var null|Field $field */
        $field = $this->repository->find($command->field);

        if (!$field || $field->isRemoved) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(FieldVoter::UPDATE_FIELD, $field)) {
            throw new AccessDeniedHttpException();
        }

        $field->name        = $command->name;
        $field->description = $command->description;
        $field->isRequired  = $command->required;

        $field = $this->copyCommandToField($this->translator, $this->manager, $command, $field);

        $errors = $this->validator->validate($field);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($field);
    }
}
