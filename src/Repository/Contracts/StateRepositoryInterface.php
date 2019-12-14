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
use eTraxis\Entity\State;

/**
 * Interface to the 'State' entities repository.
 */
interface StateRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param State $entity
     */
    public function persist(State $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param State $entity
     */
    public function remove(State $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::refresh()
     *
     * @param State $entity
     */
    public function refresh(State $entity): void;
}
