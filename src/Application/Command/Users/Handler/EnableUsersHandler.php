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

namespace eTraxis\Application\Command\Users\Handler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Users\EnableUsersCommand;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class EnableUsersHandler
{
    private AuthorizationCheckerInterface $security;
    private UserRepositoryInterface       $repository;
    private EntityManagerInterface        $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param UserRepositoryInterface       $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        UserRepositoryInterface       $repository,
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
     * @param EnableUsersCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(EnableUsersCommand $command): void
    {
        $ids = array_unique($command->users);

        /** @var User[] $accounts */
        $accounts = $this->repository->findBy([
            'id' => $ids,
        ]);

        if (count($accounts) !== count($ids)) {
            throw new NotFoundHttpException();
        }

        $accounts = array_filter($accounts, fn (User $user) => !$user->isEnabled());

        foreach ($accounts as $account) {
            if (!$account->isEnabled() && !$this->security->isGranted(UserVoter::ENABLE_USER, $account)) {
                throw new AccessDeniedHttpException();
            }
        }

        $query = $this->manager->createQuery('
            UPDATE eTraxis:User u
            SET u.isEnabled = :state
            WHERE u.id IN (:ids)
        ');

        $query->execute([
            'ids'   => $ids,
            'state' => 1,
        ]);
    }
}
