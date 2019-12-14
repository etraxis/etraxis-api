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

namespace eTraxis\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webinarium\PropertyTrait;

/**
 * Field embedded parameters.
 *
 * @ORM\Embeddable
 *
 * @property null|int $parameter1   First parameter of the field. Depends on field type.
 * @property null|int $parameter2   Second parameter of the field. Depends on field type.
 * @property null|int $defaultValue Default value of the field. Depends on field type.
 */
class FieldParameters
{
    use PropertyTrait;

    /**
     * @var int First parameter of the field. Depends on field type as following:
     *
     *      number   - minimum of range of allowed values (from -1000000000 till +1000000000)
     *      decimal  - maximum of range of allowed values (foreign key to "DecimalValue" entity)
     *      string   - maximum allowed length of values (up to 250)
     *      text     - maximum allowed length of values (up to 10000)
     *      checkbox - NULL (not used)
     *      list     - NULL (not used)
     *      issue    - NULL (not used)
     *      date     - minimum of range of allowed values (amount of days since current date; negative value shifts to the past)
     *      duration - minimum of range of allowed values (amount of minutes from 0:00 till 999999:59)
     *
     * @ORM\Column(name="parameter1", type="integer", nullable=true)
     */
    protected $parameter1;

    /**
     * @var int Second parameter of the field. Depends on field type as following:
     *
     *      number   - maximum of range of allowed values (from -1000000000 till +1000000000)
     *      decimal  - maximum of range of allowed values (foreign key to "DecimalValue" entity)
     *      string   - NULL (not used)
     *      text     - NULL (not used)
     *      checkbox - NULL (not used)
     *      list     - NULL (not used)
     *      issue    - NULL (not used)
     *      date     - maximum of range of allowed values (amount of days since current date; negative value shifts to the past)
     *      duration - maximum of range of allowed values (amount of minutes from 0:00 till 999999:59)
     *
     * @ORM\Column(name="parameter2", type="integer", nullable=true)
     */
    protected $parameter2;

    /**
     * @var int Default value of the field. Depends on field type as following:
     *
     *      number   - integer value (from -1000000000 till +1000000000)
     *      decimal  - decimal value (foreign key to "DecimalValue" entity)
     *      string   - string value (foreign key to "StringValue" entity)
     *      text     - string value (foreign key to "TextValue" entity)
     *      checkbox - state of checkbox (0 - unchecked, 1 - checked)
     *      list     - integer value (foreign key to "ListItem" entity)
     *      issue    - NULL (not used)
     *      date     - default date value (amount of days since current date; negative value shifts to the past)
     *      duration - duration value (amount of minutes from 0:00 till 999999:59)
     *
     * @ORM\Column(name="default_value", type="integer", nullable=true)
     */
    protected $defaultValue;
}
