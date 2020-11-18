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
use eTraxis\Entity\Project;

/**
 * Interface to the 'Project' entities repository.
 */
interface ProjectRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     *
     * @param Project $entity
     */
    public function persist(Project $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     *
     * @param Project $entity
     */
    public function remove(Project $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::refresh()
     *
     * @param Project $entity
     */
    public function refresh(Project $entity): void;

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
