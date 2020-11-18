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
 * Updates profile info of the current user.
 *
 * @property string $email    New email address.
 * @property string $fullname New full name.
 */
class UpdateProfileCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="254")
     * @Assert\Email
     */
    public ?string $email;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     */
    public ?string $fullname;
}
