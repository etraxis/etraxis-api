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

use eTraxis\Application\Command\Fields\DeleteFieldCommand;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\Voter\FieldVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteFieldHandler
{
    private AuthorizationCheckerInterface $security;
    private FieldRepositoryInterface      $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param FieldRepositoryInterface      $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, FieldRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteFieldCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(DeleteFieldCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Field $field */
        $field = $this->repository->find($command->field);

        if ($field && !$field->isRemoved) {

            if (!$this->security->isGranted(FieldVoter::REMOVE_FIELD, $field)) {
                throw new AccessDeniedHttpException();
            }

            $position = $field->position;
            $fields   = $field->state->fields;

            if ($this->security->isGranted(FieldVoter::DELETE_FIELD, $field)) {
                $this->repository->remove($field);
            }
            else {
                $field->remove();
                $this->repository->persist($field);
            }

            // Reorder remaining fields.
            foreach ($fields as $field) {
                if ($field->position > $position) {
                    $field->position = $field->position - 1;
                    $this->repository->persist($field);
                }
            }
        }
    }
}
