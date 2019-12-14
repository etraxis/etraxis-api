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

use eTraxis\Entity\ListItem;

/**
 * List field interface.
 */
interface ListInterface extends FieldInterface
{
    /**
     * Sets default value of the field.
     *
     * @param null|ListItem $value
     *
     * @return self
     */
    public function setDefaultValue(?ListItem $value): self;

    /**
     * Returns default value of the field.
     *
     * @return null|ListItem
     */
    public function getDefaultValue(): ?ListItem;
}
