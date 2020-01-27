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
use Doctrine\Persistence\ObjectRepository;
use eTraxis\Entity\File;

/**
 * Interface to the 'File' entities repository.
 */
interface FileRepositoryInterface extends CachedRepositoryInterface, ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     *
     * @param File $entity
     */
    public function persist(File $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     *
     * @param File $entity
     */
    public function remove(File $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::refresh()
     *
     * @param File $entity
     */
    public function refresh(File $entity): void;

    /**
     * @see \Doctrine\ORM\EntityRepository::createQueryBuilder()
     *
     * @param string $alias
     * @param string $indexBy
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createQueryBuilder(string $alias, ?string $indexBy = null);

    /**
     * Returns absolute path including filename to the specified attachment.
     *
     * @param File $entity
     *
     * @return null|string
     */
    public function getFullPath(File $entity): ?string;
}
