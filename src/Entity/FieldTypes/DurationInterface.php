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
 * Duration field interface.
 */
interface DurationInterface extends FieldInterface
{
    // Constraints.
    public const MIN_VALUE = 0;            // 0:00
    public const MAX_VALUE = 59999999;     // 999999:59

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

    /**
     * Converts specified string representation of amount of minutes to an integer number.
     *
     * @param null|string $value String representation.
     *
     * @return null|int Number of minutes (e.g. 127 for "2:07").
     */
    public function toNumber(?string $value): ?int;

    /**
     * Converts specified number of minutes to its string representation in format "hh:mm".
     *
     * @param null|int $value Number of minutes.
     *
     * @return null|string String representation (e.g. "2:07" for 127).
     */
    public function toString(?int $value): ?string;
}
