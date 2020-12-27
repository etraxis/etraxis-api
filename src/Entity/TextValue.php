<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webinarium\PropertyTrait;

/**
 * Text value.
 *
 * @ORM\Table(name="text_values")
 * @ORM\Entity(repositoryClass="eTraxis\Repository\TextValueRepository")
 *
 * @property-read int    $id    Unique ID.
 * @property-read string $value Text value.
 */
class TextValue implements \JsonSerializable
{
    use PropertyTrait;

    // Constraints.
    public const MAX_VALUE = 10000;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @var string Value token.
     *
     * @ORM\Column(name="token", type="string", length=32, unique=true)
     */
    protected string $token;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text")
     */
    protected string $value;

    /**
     * Creates new text value.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->token = md5($value);
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->value;
    }
}
