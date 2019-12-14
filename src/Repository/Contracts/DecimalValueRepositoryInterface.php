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
use eTraxis\Entity\DecimalValue;

/**
 * Interface to the 'DecimalValue' entities repository.
 */
interface DecimalValueRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param DecimalValue $entity
     */
    public function persist(DecimalValue $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param DecimalValue $entity
     */
    public function remove(DecimalValue $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::refresh()
     *
     * @param DecimalValue $entity
     */
    public function refresh(DecimalValue $entity): void;

    /**
     * Finds specified decimal value entity.
     * If the value doesn't exist yet, creates it.
     *
     * @param string $value Decimal value.
     *
     * @return DecimalValue
     */
    public function get(string $value): DecimalValue;
}
