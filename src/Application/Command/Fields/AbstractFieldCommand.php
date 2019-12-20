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

namespace eTraxis\Application\Command\Fields;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract "Create/update field" command.
 * Contains properties which are common for all commands to create or update a field of any type.
 *
 * @property string $name        Field name.
 * @property string $description Description.
 * @property bool   $required    Whether the field is required.
 */
abstract class AbstractFieldCommand
{
    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     */
    public $name;

    /**
     * @Assert\Length(max="1000")
     */
    public $description;

    /**
     * @Assert\NotNull
     */
    public $required;
}
