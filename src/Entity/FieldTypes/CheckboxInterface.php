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

/**
 * Checkbox field interface.
 */
interface CheckboxInterface extends FieldInterface
{
    /**
     * Sets default value of the field.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setDefaultValue(bool $value): self;

    /**
     * Returns default value of the field.
     *
     * @return bool
     */
    public function getDefaultValue(): bool;
}
