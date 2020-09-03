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

namespace eTraxis\Application\Command\Groups;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Creates new group.
 *
 * @property int    $project     ID of the group's project (empty for global group).
 * @property string $name        Group name.
 * @property string $description Description.
 */
class CreateGroupCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\Regex("/^\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", example=123, description="Project ID (null for global group).")
     */
    public ?int $project = null;

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
