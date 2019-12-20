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
 * Trait for "text" field commands.
 *
 * @property int    $maxlength   Maximum allowed length of field values.
 * @property string $default     TextValue ID.
 * @property string $pcreCheck   Perl-compatible regular expression which values of the field must conform to.
 * @property string $pcreSearch  Perl-compatible regular expression to modify values of the field before display them (search for).
 * @property string $pcreReplace Perl-compatible regular expression to modify values of the field before display them (replace with).
 */
trait TextCommandTrait
{
    /**
     * @Assert\NotBlank
     * @Assert\Range(min="1", max="10000")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $maxlength;

    /**
     * @Assert\Length(max="10000")
     */
    public $default;

    /**
     * @Assert\Length(max="500")
     */
    public $pcreCheck;

    /**
     * @Assert\Length(max="500")
     */
    public $pcreSearch;

    /**
     * @Assert\Length(max="500")
     */
    public $pcreReplace;
}
