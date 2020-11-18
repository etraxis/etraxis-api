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
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldValue;
use eTraxis\Entity\Issue;
use eTraxis\Entity\User;

/**
 * Interface to the 'FieldValue' entities repository.
 */
interface FieldValueRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     *
     * @param FieldValue $entity
     */
    public function persist(FieldValue $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     *
     * @param FieldValue $entity
     */
    public function remove(FieldValue $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::refresh()
     *
     * @param FieldValue $entity
     */
    public function refresh(FieldValue $entity): void;

    /**
     * Returns human-readable version of the specified field value.
     *
     * @param FieldValue $fieldValue Field value.
     * @param User       $user       Current user.
     *
     * @return null|mixed Human-readable value.
     */
    public function getFieldValue(FieldValue $fieldValue, User $user);

    /**
     * Sets value of the specified field in the specified issue.
     *
     * @param Issue      $issue Issie whose field is being set.
     * @param Event      $event Event related to this change.
     * @param Field      $field Field to set.
     * @param null|mixed $value Value to set.
     *
     * @return null|FieldValue In case of an error returns NULL.
     */
    public function setFieldValue(Issue $issue, Event $event, Field $field, $value): ?FieldValue;
}
