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

namespace eTraxis\Application\Command\Templates;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Creates new template.
 *
 * @property int    $project     ID of the template's project.
 * @property string $name        Template name.
 * @property string $prefix      Template prefix.
 * @property string $description Description.
 * @property int    $critical    Critical age.
 * @property int    $frozen      Frozen time.
 */
class CreateTemplateCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", example=123, description="Project ID.")
     */
    public ?int $project;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=50, example="Bugfix", description="Template name.")
     */
    public ?string $name;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="5")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=5, example="bug", description="Template prefix.")
     */
    public ?string $prefix;

    /**
     * @Assert\Length(max="100")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=100, example="Error reports", description="Optional description.")
     */
    public ?string $description = null;

    /**
     * @Assert\Range(min="1", max="100")
     *
     * @Groups("api")
     * @API\Property(type="integer", minimum=1, maximum=100, example=5, description="'Critical Age' value.")
     */
    public ?int $critical = null;

    /**
     * @Assert\Range(min="1", max="100")
     *
     * @Groups("api")
     * @API\Property(type="integer", minimum=1, maximum=100, example=10, description="'Frozen Time' value.")
     */
    public ?int $frozen = null;
}
