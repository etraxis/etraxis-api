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
 * Decimal field interface.
 */
interface DecimalInterface extends FieldInterface
{
    // Constraints.
    public const MIN_VALUE = '-9999999999.9999999999';
    public const MAX_VALUE = '9999999999.9999999999';

    /**
     * Sets minimum allowed value of the field.
     *
     * @param string $value
     *
     * @return self
     */
    public function setMinimumValue(string $value): self;

    /**
     * Returns minimum allowed value of the field.
     *
     * @return string
     */
    public function getMinimumValue(): string;

    /**
     * Sets maximum allowed value of the field.
     *
     * @param string $value
     *
     * @return self
     */
    public function setMaximumValue(string $value): self;

    /**
     * Returns maximum allowed value of the field.
     *
     * @return string
     */
    public function getMaximumValue(): string;

    /**
     * Sets default value of the field.
     *
     * @param null|string $value
     *
     * @return self
     */
    public function setDefaultValue(?string $value): self;

    /**
     * Returns default value of the field.
     *
     * @return null|string
     */
    public function getDefaultValue(): ?string;
}
