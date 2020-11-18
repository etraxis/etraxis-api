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

namespace eTraxis\Application\Command\Fields\Handler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Fields\SetRolesPermissionCommand;
use eTraxis\Entity\FieldRolePermission;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\Voter\FieldVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetRolesPermissionHandler
{
    private AuthorizationCheckerInterface $security;
    private FieldRepositoryInterface      $repository;
    private EntityManagerInterface        $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param FieldRepositoryInterface      $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        FieldRepositoryInterface      $repository,
        EntityManagerInterface        $manager
    )
    {
        $this->security   = $security;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

    /**
     * Command handler.
     *
     * @param SetRolesPermissionCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetRolesPermissionCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Field $field */
        $field = $this->repository->find($command->field);

        if (!$field) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(FieldVoter::SET_PERMISSIONS, $field)) {
            throw new AccessDeniedHttpException();
        }

        // Remove all roles which are supposed to not be granted with specified permission, but they currently are.
        $permissions = array_filter($field->rolePermissions, fn (FieldRolePermission $permission) => $permission->permission === $command->permission);

        foreach ($permissions as $permission) {
            if (!in_array($permission->role, $command->roles, true)) {
                $this->manager->remove($permission);
            }
        }

        // Update all roles which are supposed to be granted with specified permission, but they currently are granted with another permission.
        foreach ($field->rolePermissions as $permission) {
            if (in_array($permission->role, $command->roles, true) && $permission->permission !== $command->permission) {
                $permission->permission = $command->permission;
                $this->manager->persist($permission);
            }
        }

        // Add all roles which are supposed to be granted with specified permission, but they currently are not.
        $existingRoles = array_map(fn (FieldRolePermission $permission) => $permission->role, $field->rolePermissions);

        foreach ($command->roles as $role) {
            if (!in_array($role, $existingRoles, true)) {
                $permission = new FieldRolePermission($field, $role, $command->permission);
                $this->manager->persist($permission);
            }
        }
    }
}
