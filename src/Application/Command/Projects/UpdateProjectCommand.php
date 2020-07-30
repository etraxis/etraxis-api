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

namespace eTraxis\Application\Command\Projects;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified project.
 *
 * @property int    $project     Project ID.
 * @property string $name        New name.
 * @property string $description New description.
 * @property bool   $suspended   New status.
 */
class UpdateProjectCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public int $project;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="25")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=25, example="Project A", description="Project name.")
     */
    public string $name;

    /**
     * @Assert\Length(max="100")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=100, example="Our initial startup", description="Optional description.")
     */
    public ?string $description = null;

    /**
     * @Assert\NotNull
     *
     * @Groups("api")
     * @API\Property(type="boolean", example=false, description="Whether should be suspended.")
     */
    public bool $suspended;
}
