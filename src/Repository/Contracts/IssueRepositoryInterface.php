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

namespace eTraxis\Repository\Contracts;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Persistence\ObjectRepository;
use eTraxis\Entity\Issue;

/**
 * Interface to the 'Issue' entities repository.
 */
interface IssueRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param Issue $entity
     */
    public function persist(Issue $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param Issue $entity
     */
    public function remove(Issue $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::refresh()
     *
     * @param Issue $entity
     */
    public function refresh(Issue $entity): void;
}
