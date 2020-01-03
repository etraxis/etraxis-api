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

namespace eTraxis\Entity\FieldTypes;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Generic field interface.
 */
interface FieldInterface extends \JsonSerializable
{
    /**
     * Returns list of constraints for field value validation.
     *
     * @param TranslatorInterface $translator Translation service to configure error messages.
     * @param null|int            $timestamp  Timestamp when current value of the field being validated has been created, if applicable.
     *
     * @return \Symfony\Component\Validator\Constraint[]
     */
    public function getValidationConstraints(TranslatorInterface $translator, ?int $timestamp = null): array;
}
