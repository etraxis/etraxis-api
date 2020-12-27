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
use eTraxis\Application\Dictionary\FieldPermission;
use Webinarium\PropertyTrait;

/**
 * Field permission for group.
 *
 * @ORM\Table(name="field_group_permissions")
 * @ORM\Entity
 *
 * @property-read Field  $field      Field.
 * @property-read Group  $group      Group.
 * @property      string $permission Permission granted to the group for this field.
 */
class FieldGroupPermission implements \JsonSerializable
{
    use PropertyTrait;

    // JSON properties.
    public const JSON_GROUP      = 'group';
    public const JSON_PERMISSION = 'permission';

    /**
     * @var Field
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Field", inversedBy="groupPermissionsCollection")
     * @ORM\JoinColumn(name="field_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected Field $field;

    /**
     * @var Group
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\Group")
     * @ORM\JoinColumn(name="group_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected Group $group;

    /**
     * @var string
     *
     * @ORM\Column(name="permission", type="string", length=20)
     */
    protected string $permission;

    /**
     * Constructor.
     *
     * @param Field  $field
     * @param Group  $group
     * @param string $permission
     */
    public function __construct(Field $field, Group $group, string $permission)
    {
        if (!$group->isGlobal && $group->project !== $field->state->template->project) {
            throw new \UnexpectedValueException('Unknown group: ' . $group->name);
        }

        if (!FieldPermission::has($permission)) {
            throw new \UnexpectedValueException('Unknown permission: ' . $permission);
        }

        $this->field      = $field;
        $this->group      = $group;
        $this->permission = $permission;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_GROUP      => $this->group->id,
            self::JSON_PERMISSION => $this->permission,
        ];
    }
}
