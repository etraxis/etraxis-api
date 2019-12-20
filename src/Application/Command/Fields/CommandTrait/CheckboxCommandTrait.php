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
 * Trait for "checkbox" field commands.
 *
 * @property bool $default Default value of the field.
 */
trait CheckboxCommandTrait
{
    /**
     * @Assert\NotNull
     */
    public $default;
}
