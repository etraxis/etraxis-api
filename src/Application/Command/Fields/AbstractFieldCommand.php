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

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
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
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=50, example="Severity", description="Field name.")
     */
    public string $name;

    /**
     * @Assert\Length(max="1000")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=1000, example="Error severity", description="Optional description.")
     */
    public ?string $description = null;

    /**
     * @Assert\NotNull
     *
     * @Groups("api")
     * @API\Property(type="boolean", example=true, description="Whether should be required.")
     */
    public bool $required;
}
