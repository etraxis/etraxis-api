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
use eTraxis\Application\Command\Issues\UnwatchIssuesCommand;
use eTraxis\Entity\Watcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
class UnwatchIssuesHandler
{
    private TokenStorageInterface  $tokenStorage;
    private EntityManagerInterface $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TokenStorageInterface  $tokenStorage
     * @param EntityManagerInterface $manager
     */
    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $manager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->manager      = $manager;
    }

    /**
     * Command handler.
     *
     * @param UnwatchIssuesCommand $command
     */
    public function __invoke(UnwatchIssuesCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $query = $this->manager->createQueryBuilder();

        $query
            ->delete(Watcher::class, 'w')
            ->where('w.user = :user')
            ->andWhere($query->expr()->in('w.issue', ':issues'));

        $query->getQuery()->execute([
            'user'   => $user,
            'issues' => $command->issues,
        ]);
    }
}
