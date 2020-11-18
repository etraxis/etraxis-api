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

namespace eTraxis\Entity;

use eTraxis\Application\Dictionary\AccountProvider;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\AccountInfo
 */
class AccountInfoTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $account = new AccountInfo();

        self::assertSame(AccountProvider::ETRAXIS, $account->provider);
        self::assertRegExp('/^([[:xdigit:]]{32})$/is', $account->uid);
    }

    /**
     * @covers ::setters
     */
    public function testProvider()
    {
        $account = new AccountInfo();
        self::assertSame(AccountProvider::ETRAXIS, $account->provider);

        $account->provider = AccountProvider::LDAP;
        self::assertSame(AccountProvider::LDAP, $account->provider);
    }

    /**
     * @covers ::setters
     */
    public function testProviderException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown account provider: acme');

        $account = new AccountInfo();

        $account->provider = 'acme';
    }
}
