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
 * Trait for "date" field commands.
 *
 * @property int $minimum Amount of days since current date (negative value shifts to the past).
 * @property int $maximum Amount of days since current date (negative value shifts to the past).
 * @property int $default Amount of days since current date (negative value shifts to the past).
 */
trait DateCommandTrait
{
    /**
     * @Assert\NotBlank
     * @Assert\Range(min="-2147483648", max="2147483647")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $minimum;

    /**
     * @Assert\NotBlank
     * @Assert\Range(min="-2147483648", max="2147483647")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $maximum;

    /**
     * @Assert\Range(min="-2147483648", max="2147483647")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $default;
}
