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
 * String value.
 *
 * @ORM\Table(name="string_values")
 * @ORM\Entity(repositoryClass="eTraxis\Repository\StringValueRepository")
 *
 * @property-read int    $id    Unique ID.
 * @property-read string $value String value.
 */
class StringValue implements \JsonSerializable
{
    use PropertyTrait;

    // Constraints.
    public const MAX_VALUE = 250;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string Value token.
     *
     * @ORM\Column(name="token", type="string", length=32, unique=true)
     */
    protected $token;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=250)
     */
    protected $value;

    /**
     * Creates new string value.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->token = md5($value);
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->value;
    }
}
