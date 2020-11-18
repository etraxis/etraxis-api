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

namespace eTraxis\Entity\FieldTypes;

use eTraxis\Entity\FieldPCRE;
use eTraxis\Entity\TextValue;

/**
 * Text field interface.
 */
interface TextInterface extends FieldInterface
{
    // Constraints.
    public const MIN_LENGTH = 1;
    public const MAX_LENGTH = TextValue::MAX_VALUE;

    /**
     * Sets maximum allowed length of field values.
     *
     * @param int $length
     *
     * @return self
     */
    public function setMaximumLength(int $length): self;

    /**
     * Returns maximum allowed length of field values.
     *
     * @return int
     */
    public function getMaximumLength(): int;

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
     * Returns PCRE options of the field.
     *
     * @return FieldPCRE
     */
    public function getPCRE(): FieldPCRE;
}
