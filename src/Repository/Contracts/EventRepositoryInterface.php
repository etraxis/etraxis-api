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
use eTraxis\Entity\Event;

/**
 * Interface to the 'Event' entities repository.
 */
interface EventRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param Event $entity
     */
    public function persist(Event $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param Event $entity
     */
    public function remove(Event $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::refresh()
     *
     * @param Event $entity
     */
    public function refresh(Event $entity): void;
}
