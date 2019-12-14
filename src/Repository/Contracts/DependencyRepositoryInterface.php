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
use eTraxis\Entity\Dependency;

/**
 * Interface to the 'Dependency' entities repository.
 */
interface DependencyRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param Dependency $entity
     */
    public function persist(Dependency $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param Dependency $entity
     */
    public function remove(Dependency $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::refresh()
     *
     * @param Dependency $entity
     */
    public function refresh(Dependency $entity): void;
}
