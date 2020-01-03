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

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait for "string" field commands.
 *
 * @property int    $maxlength   Maximum allowed length of field values.
 * @property string $default     StringValue ID.
 * @property string $pcreCheck   Perl-compatible regular expression which values of the field must conform to.
 * @property string $pcreSearch  Perl-compatible regular expression to modify values of the field before display them (search for).
 * @property string $pcreReplace Perl-compatible regular expression to modify values of the field before display them (replace with).
 */
trait StringCommandTrait
{
    /**
     * @Assert\NotBlank
     * @Assert\Range(min="1", max="250")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", minimum=1, maximum=250, example=100, description="Maximum length.")
     */
    public $maxlength;

    /**
     * @Assert\Length(max="250")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=250, example="Message subject", description="Default value.")
     */
    public $default;

    /**
     * @Assert\Length(max="500")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=500, example="(\d{3})-(\d{3})-(\d{4})", description="Perl-compatible regular expression.")
     */
    public $pcreCheck;

    /**
     * @Assert\Length(max="500")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=500, example="(\d{3})-(\d{3})-(\d{4})", description="Perl-compatible regular expression.")
     */
    public $pcreSearch;

    /**
     * @Assert\Length(max="500")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=500, example="($1) $2-$3", description="Perl-compatible regular expression.")
     */
    public $pcreReplace;
}
