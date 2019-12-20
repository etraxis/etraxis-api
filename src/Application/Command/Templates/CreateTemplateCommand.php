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
     */
    public $project;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     */
    public $name;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="5")
     */
    public $prefix;

    /**
     * @Assert\Length(max="100")
     */
    public $description;

    /**
     * @Assert\Range(min="1", max="100")
     */
    public $critical;

    /**
     * @Assert\Range(min="1", max="100")
     */
    public $frozen;
}
