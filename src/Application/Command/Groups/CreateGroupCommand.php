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
     */
    public $project;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="25")
     */
    public $name;

    /**
     * @Assert\Length(max="100")
     */
    public $description;
}
