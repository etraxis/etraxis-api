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
 * Creates new account.
 *
 * @property string $email       Email address.
 * @property string $password    Password.
 * @property string $fullname    Full name.
 * @property string $description Description.
 * @property bool   $admin       Role (whether has administrator permissions).
 * @property bool   $disabled    Status.
 * @property string $locale      Locale.
 * @property string $timezone    Timezone.
 */
class CreateUserCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="254")
     * @Assert\Email
     */
    public $email;

    /**
     * @Assert\NotBlank
     */
    public $password;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     */
    public $fullname;

    /**
     * @Assert\Length(max="100")
     */
    public $description;

    /**
     * @Assert\NotNull
     */
    public $admin;

    /**
     * @Assert\NotNull
     */
    public $disabled;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\Locale", "keys"}, strict=true)
     */
    public $locale;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\Timezone", "values"}, strict=true)
     */
    public $timezone;
}
