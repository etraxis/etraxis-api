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
use eTraxis\Application\Dictionary\AccountProvider;
use Ramsey\Uuid\Uuid;
use Webinarium\PropertyTrait;

/**
 * Support for different account sources (LDAP, OAuth, etc).
 *
 * @ORM\Embeddable
 *
 * @property string $provider Account provider (see the "AccountProvider" dictionary).
 * @property string $uid      Account UID as in the external provider's system.
 */
class AccountInfo
{
    use PropertyTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="provider", type="string", length=20)
     */
    protected string $provider;

    /**
     * @var string
     *
     * @ORM\Column(name="uid", type="string", length=128)
     */
    protected string $uid;

    /**
     * Initializes properties as for internal account.
     */
    public function __construct()
    {
        $this->provider = AccountProvider::ETRAXIS;
        $this->uid      = Uuid::uuid4()->getHex()->toString();
    }

    /**
     * {@inheritdoc}
     */
    protected function setters(): array
    {
        return [

            'provider' => function (string $provider): void {
                if (AccountProvider::has($provider)) {
                    $this->provider = $provider;
                }
                else {
                    throw new \UnexpectedValueException('Unknown account provider: ' . $provider);
                }
            },
        ];
    }
}
