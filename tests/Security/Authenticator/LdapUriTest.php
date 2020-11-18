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

namespace eTraxis\Security\Authenticator;

use eTraxis\Application\Dictionary\LdapServerType;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Security\Authenticator\LdapUri
 */
class LdapUriTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testNone()
    {
        $uri = new LdapUri('null://example.com');

        self::assertSame(LdapUri::SCHEME_NULL, $uri->scheme);
        self::assertSame('example.com', $uri->host);
        self::assertSame(389, $uri->port);
        self::assertSame(LdapServerType::POSIX, $uri->type);
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->encryption);
        self::assertEmpty($uri->username);
        self::assertEmpty($uri->password);
    }

    /**
     * @covers ::__construct
     */
    public function testLdapWithUser()
    {
        $uri = new LdapUri('ldap://root@example.com');

        self::assertSame(LdapUri::SCHEME_LDAP, $uri->scheme);
        self::assertSame('example.com', $uri->host);
        self::assertSame(389, $uri->port);
        self::assertSame(LdapServerType::POSIX, $uri->type);
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->encryption);
        self::assertSame('root', $uri->username);
        self::assertEmpty($uri->password);
    }

    /**
     * @covers ::__construct
     */
    public function testLdapsWithUserPassword()
    {
        $uri = new LdapUri('ldaps://root:secret@example.com');

        self::assertSame(LdapUri::SCHEME_LDAPS, $uri->scheme);
        self::assertSame('example.com', $uri->host);
        self::assertSame(389, $uri->port);
        self::assertSame(LdapServerType::POSIX, $uri->type);
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->encryption);
        self::assertSame('root', $uri->username);
        self::assertSame('secret', $uri->password);
    }

    /**
     * @covers ::__construct
     */
    public function testPort()
    {
        $uri = new LdapUri('ldap://example.com:389');

        self::assertSame(LdapUri::SCHEME_LDAP, $uri->scheme);
        self::assertSame('example.com', $uri->host);
        self::assertSame(389, $uri->port);
        self::assertSame(LdapServerType::POSIX, $uri->type);
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->encryption);
        self::assertEmpty($uri->username);
        self::assertEmpty($uri->password);
    }

    /**
     * @covers ::__construct
     */
    public function testType()
    {
        $uri = new LdapUri('ldap://example.com?type=win2000');

        self::assertSame(LdapUri::SCHEME_LDAP, $uri->scheme);
        self::assertSame('example.com', $uri->host);
        self::assertSame(389, $uri->port);
        self::assertSame(LdapServerType::WIN2000, $uri->type);
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->encryption);
        self::assertEmpty($uri->username);
        self::assertEmpty($uri->password);
    }

    /**
     * @covers ::__construct
     */
    public function testEncryption()
    {
        $uri = new LdapUri('ldap://example.com?encryption=tls');

        self::assertSame(LdapUri::SCHEME_LDAP, $uri->scheme);
        self::assertSame('example.com', $uri->host);
        self::assertSame(389, $uri->port);
        self::assertSame(LdapServerType::POSIX, $uri->type);
        self::assertSame(LdapUri::ENCRYPTION_TLS, $uri->encryption);
        self::assertEmpty($uri->username);
        self::assertEmpty($uri->password);
    }

    /**
     * @covers ::__construct
     */
    public function testMaximum()
    {
        $uri = new LdapUri('ldaps://root:secret@example.com:636?type=winnt&encryption=ssl');

        self::assertSame(LdapUri::SCHEME_LDAPS, $uri->scheme);
        self::assertSame('example.com', $uri->host);
        self::assertSame(636, $uri->port);
        self::assertSame(LdapServerType::WINNT, $uri->type);
        self::assertSame(LdapUri::ENCRYPTION_SSL, $uri->encryption);
        self::assertSame('root', $uri->username);
        self::assertSame('secret', $uri->password);
    }

    /**
     * @covers ::__construct
     */
    public function testInvalidSchema()
    {
        $uri = new LdapUri('ssh://root:secret@example.com');

        self::assertSame(LdapUri::SCHEME_NULL, $uri->scheme);
        self::assertSame('example.com', $uri->host);
        self::assertSame(389, $uri->port);
        self::assertSame(LdapServerType::POSIX, $uri->type);
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->encryption);
        self::assertSame('root', $uri->username);
        self::assertSame('secret', $uri->password);
    }

    /**
     * @covers ::__construct
     */
    public function testEmptyHost()
    {
        $uri = new LdapUri('ldap://root:secret@');

        self::assertSame(LdapUri::SCHEME_NULL, $uri->scheme);
    }

    /**
     * @covers ::__construct
     */
    public function testInvalidType()
    {
        $uri = new LdapUri('ldap://example.com?type=acme');

        self::assertSame(LdapUri::SCHEME_LDAP, $uri->scheme);
        self::assertSame('example.com', $uri->host);
        self::assertSame(389, $uri->port);
        self::assertSame(LdapServerType::POSIX, $uri->type);
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->encryption);
        self::assertEmpty($uri->username);
        self::assertEmpty($uri->password);
    }
}
