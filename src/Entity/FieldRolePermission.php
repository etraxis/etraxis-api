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
use eTraxis\Application\Dictionary\SystemRole;
use Webinarium\PropertyTrait;

/**
 * Field permission for system role.
 *
 * @ORM\Table(name="field_role_permissions")
 * @ORM\Entity
 *
 * @property-read Field  $field      Field.
 * @property-read string $role       System role.
 * @property      string $permission Permission granted to the role for this field.
 */
class FieldRolePermission implements \JsonSerializable
{
    use PropertyTrait;

    // JSON properties.
    public const JSON_ROLE       = 'role';
    public const JSON_PERMISSION = 'permission';

    /**
     * @var Field
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Field", inversedBy="rolePermissionsCollection")
     * @ORM\JoinColumn(name="field_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected Field $field;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="role", type="string", length=20)
     */
    protected string $role;

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
     * @param string $role
     * @param string $permission
     */
    public function __construct(Field $field, string $role, string $permission)
    {
        if (!SystemRole::has($role)) {
            throw new \UnexpectedValueException('Unknown system role: ' . $role);
        }

        if (!FieldPermission::has($permission)) {
            throw new \UnexpectedValueException('Unknown permission: ' . $permission);
        }

        $this->field      = $field;
        $this->role       = $role;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_ROLE       => $this->role,
            self::JSON_PERMISSION => $this->permission,
        ];
    }
}
