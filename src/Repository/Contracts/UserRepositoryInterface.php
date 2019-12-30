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
use Doctrine\ORM\QueryBuilder;
use eTraxis\Entity\User;
use LazySec\Repository\UserRepositoryInterface as LazySecUserRepositoryInterface;

/**
 * Interface to the 'User' entities repository.
 */
interface UserRepositoryInterface extends LazySecUserRepositoryInterface, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param User $entity
     */
    public function persist(User $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param User $entity
     */
    public function remove(User $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::refresh()
     *
     * @param User $entity
     */
    public function refresh(User $entity): void;

    /**
     * @see \Doctrine\ORM\EntityRepository::createQueryBuilder()
     *
     * @param string $alias
     * @param string $indexBy
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder(string $alias, ?string $indexBy = null);
}
