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
 * Issue field change.
 *
 * @ORM\Table(
 *     name="changes",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"event_id", "field_id"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\ChangeRepository")
 *
 * @property-read int        $id       Unique ID.
 * @property-read Event      $event    Changing event.
 * @property-read null|Field $field    Changed field (NULL for issue subject).
 * @property-read null|int   $oldValue Old value of the field (see "FieldValue::$value" for details).
 * @property-read null|int   $newValue New value of the field (see "FieldValue::$value" for details).
 */
class Change
{
    use PropertyTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumn(name="event_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $event;

    /**
     * @var Field
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\Field")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * @var int
     *
     * @ORM\Column(name="old_value", type="integer", nullable=true)
     */
    protected $oldValue;

    /**
     * @var int
     *
     * @ORM\Column(name="new_value", type="integer", nullable=true)
     */
    protected $newValue;

    /**
     * Creates new change.
     *
     * @param Event      $event
     * @param null|Field $field
     * @param null|int   $oldValue
     * @param null|int   $newValue
     */
    public function __construct(Event $event, ?Field $field, ?int $oldValue, ?int $newValue)
    {
        $this->event    = $event;
        $this->field    = $field;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }
}
