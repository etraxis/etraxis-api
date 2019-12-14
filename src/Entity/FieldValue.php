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
 * Issue field value.
 *
 * @ORM\Table(name="field_values")
 * @ORM\Entity(repositoryClass="eTraxis\Repository\FieldValueRepository")
 *
 * @property-read Issue    $issue     Issue.
 * @property-read Field    $field     Field.
 * @property      null|int $value     Current value of the field. Depends on field type.
 * @property-read int      $createdAt Unix Epoch timestamp when the value has been created.
 */
class FieldValue
{
    use PropertyTrait;

    /**
     * @var Issue
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Issue", inversedBy="valuesCollection")
     * @ORM\JoinColumn(name="issue_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $issue;

    /**
     * @var Field
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\Field")
     * @ORM\JoinColumn(name="field_id", nullable=false, referencedColumnName="id")
     */
    protected $field;

    /**
     * @var int Current field value. Depends on field type as following:
     *
     *      number   - integer value (from -1000000000 till +1000000000)
     *      decimal  - decimal value (foreign key to "DecimalValue" entity)
     *      string   - string value (foreign key to "StringValue" entity)
     *      text     - text value (foreign key to "TextValue" entity)
     *      checkbox - state of checkbox (0 - unchecked, 1 - checked)
     *      list     - integer value (foreign key to "ListItem" entity)
     *      issue    - issue ID (foreign key to "Issue" entity)
     *      date     - date value (Unix Epoch timestamp)
     *      duration - duration value (amount of minutes from 0:00 till 999999:59)
     *
     * @ORM\Column(name="value", type="integer", nullable=true)
     */
    protected $value;

    /**
     * @var int
     *
     * @ORM\Column(name="created_at", type="integer")
     */
    protected $createdAt;

    /**
     * Creates new field value.
     *
     * @param Issue    $issue
     * @param Field    $field
     * @param null|int $value
     */
    public function __construct(Issue $issue, Field $field, ?int $value)
    {
        if ($issue->state->template !== $field->state->template) {
            throw new \UnexpectedValueException('Unknown field: ' . $field->name);
        }

        $this->issue = $issue;
        $this->field = $field;
        $this->value = $value;

        $this->createdAt = time();
    }
}
