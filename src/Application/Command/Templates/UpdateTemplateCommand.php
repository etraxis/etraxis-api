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
 * Updates specified template.
 *
 * @property int    $template    Template ID.
 * @property string $name        New name.
 * @property string $prefix      New prefix.
 * @property string $description New description.
 * @property int    $critical    New critical age.
 * @property int    $frozen      New frozen time.
 */
class UpdateTemplateCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $template;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=50, example="Bugfix", description="Template name.")
     */
    public $name;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="5")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=5, example="bug", description="Template prefix.")
     */
    public $prefix;

    /**
     * @Assert\Length(max="100")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=100, example="Error reports", description="Optional description.")
     */
    public $description;

    /**
     * @Assert\Range(min="1", max="100")
     *
     * @Groups("api")
     * @API\Property(type="integer", minimum=1, maximum=100, example=5, description="'Critical Age' value.")
     */
    public $critical;

    /**
     * @Assert\Range(min="1", max="100")
     *
     * @Groups("api")
     * @API\Property(type="integer", minimum=1, maximum=100, example=10, description="'Frozen Time' value.")
     */
    public $frozen;
}
