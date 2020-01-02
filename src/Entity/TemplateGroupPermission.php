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
use eTraxis\Application\Dictionary\TemplatePermission;
use Webinarium\PropertyTrait;

/**
 * Template permission for group.
 *
 * @ORM\Table(name="template_group_permissions")
 * @ORM\Entity
 *
 * @property-read Template $template   Template.
 * @property-read Group    $group      Group.
 * @property-read string   $permission Permission granted to the group for this template.
 */
class TemplateGroupPermission implements \JsonSerializable
{
    use PropertyTrait;

    // JSON properties.
    public const JSON_GROUP      = 'group';
    public const JSON_PERMISSION = 'permission';

    /**
     * @var Template
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="groupPermissionsCollection")
     * @ORM\JoinColumn(name="template_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $template;

    /**
     * @var Group
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\Group")
     * @ORM\JoinColumn(name="group_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $group;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="permission", type="string", length=20)
     */
    protected $permission;

    /**
     * Constructor.
     *
     * @param Template $template
     * @param Group    $group
     * @param string   $permission
     */
    public function __construct(Template $template, Group $group, string $permission)
    {
        if (!$group->isGlobal && $group->project !== $template->project) {
            throw new \UnexpectedValueException('Unknown group: ' . $group->name);
        }

        if (!TemplatePermission::has($permission)) {
            throw new \UnexpectedValueException('Unknown permission: ' . $permission);
        }

        $this->template   = $template;
        $this->group      = $group;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_GROUP      => $this->group->id,
            self::JSON_PERMISSION => $this->permission,
        ];
    }
}
