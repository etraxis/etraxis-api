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

namespace eTraxis\Application\Command\Groups;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified group.
 *
 * @property int    $group       Group ID.
 * @property string $name        New name.
 * @property string $description New description.
 */
class UpdateGroupCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public ?int $group;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="25")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=25, example="Team", description="Group name.")
     */
    public ?string $name;

    /**
     * @Assert\Length(max="100")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=100, example="Project developers", description="Optional description.")
     */
    public ?string $description = null;
}
