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
 * Registers new external account, or updates it if it already exists.
 *
 * @property string $provider Account provider (see the "AccountProvider" dictionary).
 * @property string $uid      Account UID as in the external provider's system.
 * @property string $email    Email address.
 * @property string $fullname Full name.
 */
class RegisterExternalAccountCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\AccountProvider", "keys"}, strict=true)
     */
    public $provider;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="128")
     */
    public $uid;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="254")
     * @Assert\Email
     */
    public $email;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     */
    public $fullname;
}
