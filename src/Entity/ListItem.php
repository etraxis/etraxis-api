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
use eTraxis\Application\Dictionary\FieldType;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * List item.
 *
 * @ORM\Table(
 *     name="list_items",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"field_id", "item_value"}),
 *         @ORM\UniqueConstraint(columns={"field_id", "item_text"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\ListItemRepository")
 * @Assert\UniqueEntity(fields={"field", "value"}, message="listitem.conflict.value")
 * @Assert\UniqueEntity(fields={"field", "text"}, message="listitem.conflict.text")
 *
 * @property-read int    $id    Unique ID.
 * @property-read Field  $field Item's field.
 * @property      int    $value Item's value.
 * @property      string $text  Item's text.
 */
class ListItem implements \JsonSerializable
{
    use PropertyTrait;

    // Constraints.
    public const MAX_TEXT = 50;

    // JSON properties.
    public const JSON_ID    = 'id';
    public const JSON_VALUE = 'value';
    public const JSON_TEXT  = 'text';
    public const JSON_FIELD = 'field';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Field Field.
     *
     * @ORM\ManyToOne(targetEntity="Field")
     * @ORM\JoinColumn(name="field_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $field;

    /**
     * @var int Value of the item.
     *
     * @ORM\Column(name="item_value", type="integer")
     */
    protected $value;

    /**
     * @var string Text of the item.
     *
     * @ORM\Column(name="item_text", type="string", length=50)
     */
    protected $text;

    /**
     * Adds new item to specified field of "List" type.
     *
     * @param Field $field
     */
    public function __construct(Field $field)
    {
        if ($field->type !== FieldType::LIST) {
            throw new \UnexpectedValueException('Invalid field type: ' . $field->type);
        }

        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_ID    => $this->id,
            self::JSON_VALUE => $this->value,
            self::JSON_TEXT  => $this->text,
        ];
    }
}
