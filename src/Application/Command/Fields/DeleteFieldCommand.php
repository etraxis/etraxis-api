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
 * Deletes specified field.
 *
 * @property int $field Field ID.
 */
class DeleteFieldCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public ?int $field;
}
