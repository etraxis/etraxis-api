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

namespace eTraxis\Application\Command\Templates\Handler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Templates\SetGroupsPermissionCommand;
use eTraxis\Entity\Group;
use eTraxis\Entity\TemplateGroupPermission;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\Voter\TemplateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetGroupsPermissionHandler
{
    private AuthorizationCheckerInterface $security;
    private TemplateRepositoryInterface   $repository;
    private EntityManagerInterface        $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TemplateRepositoryInterface   $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TemplateRepositoryInterface   $repository,
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
        /** @var null|\eTraxis\Entity\Template $template */
        $template = $this->repository->find($command->template);

        if (!$template) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(TemplateVoter::SET_PERMISSIONS, $template)) {
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
        $permissions = array_filter($template->groupPermissions, fn (TemplateGroupPermission $permission) => $permission->permission === $command->permission);

        foreach ($permissions as $permission) {
            if (!in_array($permission->group, $requestedGroups, true)) {
                $this->manager->remove($permission);
            }
        }

        // Add all groups which are supposed to be granted with specified permission, but they currently are not.
        $existingGroups = array_map(fn (TemplateGroupPermission $permission) => $permission->group, $permissions);

        foreach ($requestedGroups as $group) {
            if (!in_array($group, $existingGroups, true)) {
                $permission = new TemplateGroupPermission($template, $group, $command->permission);
                $this->manager->persist($permission);
            }
        }
    }
}
