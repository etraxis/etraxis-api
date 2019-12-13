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

namespace eTraxis\Application\Event\Users;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Specified account successfully logged in.
 *
 * @property string $username Account's username.
 */
class LoginSuccessfulEvent
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="254")
     * @Assert\Email
     */
    public $username;
}
