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

namespace eTraxis\Application\Command\Users;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Adds account to specified groups.
 *
 * @property int   $user   User ID.
 * @property int[] $groups Group IDs.
 */
class AddGroupsCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public ?int $user;

    /**
     * @Assert\NotNull
     * @Assert\Type(type="array")
     * @Assert\Count(min="1", max="100")
     * @Assert\All({
     *     @Assert\NotBlank,
     *     @Assert\Regex("/^\d+$/")
     * })
     */
    public ?array $groups;
}
