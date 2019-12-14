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
use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;

/**
 * Interface to the 'ListItem' entities repository.
 */
interface ListItemRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param ListItem $entity
     */
    public function persist(ListItem $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param ListItem $entity
     */
    public function remove(ListItem $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::refresh()
     *
     * @param ListItem $entity
     */
    public function refresh(ListItem $entity): void;

    /**
     * Finds all list items of specified field.
     *
     * @param Field $field
     *
     * @return ListItem[]
     */
    public function findAllByField(Field $field): array;

    /**
     * Finds list item by its value.
     *
     * @param Field $field
     * @param int   $value
     *
     * @return null|ListItem
     */
    public function findOneByValue(Field $field, int $value): ?ListItem;
}
