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

namespace eTraxis\Repository\Contracts;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;
use eTraxis\Entity\Issue;
use eTraxis\Entity\LastRead;

/**
 * Interface to the 'LastRead' entities repository.
 */
interface LastReadRepositoryInterface extends CachedRepositoryInterface, ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     *
     * @param LastRead $entity
     */
    public function persist(LastRead $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     *
     * @param LastRead $entity
     */
    public function remove(LastRead $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::refresh()
     *
     * @param LastRead $entity
     */
    public function refresh(LastRead $entity): void;

    /**
     * Finds when specified issue was read last time by current user.
     *
     * @param Issue $issue
     *
     * @return null|LastRead
     */
    public function findLastRead(Issue $issue): ?LastRead;

    /**
     * Marks specified issue as read by current user.
     *
     * @param Issue $issue
     */
    public function markAsRead(Issue $issue): void;
}
