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

namespace eTraxis\Application\Command\Fields\CommandTrait;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait for "number" field commands.
 *
 * @property int $minimum Minimum allowed value.
 * @property int $maximum Maximum allowed value.
 * @property int $default Default value of the field.
 */
trait NumberCommandTrait
{
    /**
     * @Assert\NotBlank
     * @Assert\Range(min="-1000000000", max="1000000000")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $minimum;

    /**
     * @Assert\NotBlank
     * @Assert\Range(min="-1000000000", max="1000000000")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $maximum;

    /**
     * @Assert\Range(min="-1000000000", max="1000000000")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $default;
}
