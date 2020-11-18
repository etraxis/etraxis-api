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

namespace eTraxis\Application\Command\Fields;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Sets specified roles permission for the field.
 *
 * @property int      $field      Field ID.
 * @property string   $permission Field permission.
 * @property string[] $roles      Granted system roles.
 */
class SetRolesPermissionCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public ?int $field;

    /**
     * @Assert\NotBlank
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\FieldPermission", "keys"}, strict=true)
     */
    public ?string $permission;

    /**
     * @Assert\NotNull
     * @Assert\Type(type="array")
     * @Assert\Count(min="0", max="100")
     * @Assert\All({
     *     @Assert\NotBlank,
     *     @Assert\Choice(callback={"eTraxis\Application\Dictionary\SystemRole", "keys"}, strict=true)
     * })
     */
    public ?array $roles;
}
