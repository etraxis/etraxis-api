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

namespace eTraxis\Application\Command\Users;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Resets password for specified account.
 *
 * @property string $token    Token for password reset.
 * @property string $password New password.
 */
class ResetPasswordCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^[a-z0-9]{32}$/i");
     */
    public ?string $token;

    /**
     * @Assert\NotBlank
     */
    public ?string $password;
}
