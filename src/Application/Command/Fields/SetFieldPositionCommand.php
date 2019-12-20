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
use Webinarium\DataTransferObjectTrait;

/**
 * Sets new position for specified field.
 *
 * @property int $field    Field ID.
 * @property int $position Field position (one-based).
 */
class SetFieldPositionCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $field;

    /**
     * @Assert\NotBlank
     * @Assert\Range(min="1")
     * @Assert\Regex("/^\d+$/")
     */
    public $position;
}
