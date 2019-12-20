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
use eTraxis\Application\Command\Fields\SetGroupsPermissionCommand;
use eTraxis\Entity\FieldGroupPermission;
use eTraxis\Entity\Group;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\Voter\FieldVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetGroupsPermissionHandler
{
    private $security;
    private $repository;
    private $manager;

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
     * @param SetGroupsPermissionCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetGroupsPermissionCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Field $field */
        $field = $this->repository->find($command->field);

        if (!$field) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $field)) {
            throw new AccessDeniedHttpException();
        }

        // Retrieve all groups specified in the command.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'));

        $requestedGroups = $query->getQuery()->execute([
            'groups' => $command->groups,
        ]);

        // Remove all groups which are supposed to not be granted with specified permission, but they currently are.
        $permissions = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($command) {
            return $permission->permission === $command->permission;
        });

        foreach ($permissions as $permission) {
            if (!in_array($permission->group, $requestedGroups, true)) {
                $this->manager->remove($permission);
            }
        }

        // Update all groups which are supposed to be granted with specified permission, but they currently are granted with another permission.
        foreach ($field->groupPermissions as $permission) {
            if (in_array($permission->group->id, $command->groups, true) && $permission->permission !== $command->permission) {
                $permission->permission = $command->permission;
                $this->manager->persist($permission);
            }
        }

        // Add all groups which are supposed to be granted with specified permission, but they currently are not.
        $existingGroups = array_map(function (FieldGroupPermission $permission) {
            return $permission->group;
        }, $field->groupPermissions);

        foreach ($requestedGroups as $group) {
            if (!in_array($group, $existingGroups, true)) {
                $permission = new FieldGroupPermission($field, $group, $command->permission);
                $this->manager->persist($permission);
            }
        }
    }
}
