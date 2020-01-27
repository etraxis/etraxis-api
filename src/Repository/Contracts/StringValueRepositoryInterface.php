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
use eTraxis\Entity\StringValue;

/**
 * Interface to the 'StringValue' entities repository.
 */
interface StringValueRepositoryInterface extends CachedRepositoryInterface, ObjectRepository, Selectable
{
    /**
     * @param StringValue $entity
     *
     * @see \Doctrine\Persistence\ObjectManager::persist()
     */
    public function persist(StringValue $entity): void;

    /**
     * @param StringValue $entity
     *
     * @see \Doctrine\Persistence\ObjectManager::remove()
     */
    public function remove(StringValue $entity): void;

    /**
     * @param StringValue $entity
     *
     * @see \Doctrine\Persistence\ObjectManager::refresh()
     */
    public function refresh(StringValue $entity): void;

    /**
     * Finds specified string value entity.
     * If the value doesn't exist yet, creates it.
     *
     * @param string $value String value.
     *
     * @return StringValue
     */
    public function get(string $value): StringValue;
}
