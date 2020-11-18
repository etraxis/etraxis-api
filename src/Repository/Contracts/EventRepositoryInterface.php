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
use eTraxis\Entity\Event;

/**
 * Interface to the 'Event' entities repository.
 */
interface EventRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     *
     * @param Event $entity
     */
    public function persist(Event $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     *
     * @param Event $entity
     */
    public function remove(Event $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::refresh()
     *
     * @param Event $entity
     */
    public function refresh(Event $entity): void;

    /**
     * @see \Doctrine\ORM\EntityRepository::createQueryBuilder()
     *
     * @param string      $alias
     * @param null|string $indexBy
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createQueryBuilder(string $alias, ?string $indexBy = null);
}
