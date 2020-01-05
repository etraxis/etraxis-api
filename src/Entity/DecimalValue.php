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
 * Decimal value.
 *
 * @ORM\Table(name="decimal_values")
 * @ORM\Entity(repositoryClass="eTraxis\Repository\DecimalValueRepository")
 *
 * @property-read int    $id    Unique ID.
 * @property-read string $value Decimal value.
 */
class DecimalValue implements \JsonSerializable
{
    use PropertyTrait;

    // Constraints.
    public const PRECISION = 10;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="decimal", precision=20, scale=10, unique=true)
     */
    protected $value;

    /**
     * Creates new decimal value.
     *
     * @param string $value String representation of the value.
     */
    public function __construct(string $value)
    {
        $this->value = $this->trim($value);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->value;
    }

    /**
     * Trims leading and trailing extra zeros from the specified number.
     *
     * @param string $value
     *
     * @return string
     */
    protected function trim(string $value): string
    {
        $value = mb_strpos($value, '.') === false
            ? ltrim($value, '0')
            : trim($value, '0');

        if (mb_strlen($value) === 0) {
            $value = '0';
        }
        elseif ($value[0] === '.') {
            $value = '0' . $value;
        }

        return rtrim($value, '.');
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'value' => function (): string {
                return $this->trim($this->value);
            },
        ];
    }
}
