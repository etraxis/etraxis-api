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
 * Date field interface.
 */
interface DateInterface extends FieldInterface
{
    // Constraints.
    public const MIN_VALUE = -0x80000000;
    public const MAX_VALUE = 0x7FFFFFFF;

    /**
     * Sets minimum allowed value of the field.
     *
     * @param int $value
     *
     * @return self
     */
    public function setMinimumValue(int $value): self;

    /**
     * Returns minimum allowed value of the field.
     *
     * @return int
     */
    public function getMinimumValue(): int;

    /**
     * Sets maximum allowed value of the field.
     *
     * @param int $value
     *
     * @return self
     */
    public function setMaximumValue(int $value): self;

    /**
     * Returns maximum allowed value of the field.
     *
     * @return int
     */
    public function getMaximumValue(): int;

    /**
     * Sets default value of the field.
     *
     * @param null|int $value
     *
     * @return self
     */
    public function setDefaultValue(?int $value): self;

    /**
     * Returns default value of the field.
     *
     * @return null|int
     */
    public function getDefaultValue(): ?int;
}
