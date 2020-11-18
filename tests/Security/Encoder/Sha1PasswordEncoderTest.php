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

namespace eTraxis\Security\Encoder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @coversDefaultClass \eTraxis\Security\Encoder\Sha1PasswordEncoder
 */
class Sha1PasswordEncoderTest extends TestCase
{
    private Sha1PasswordEncoder $encoder;

    protected function setUp()
    {
        parent::setUp();

        $this->encoder = new Sha1PasswordEncoder();
    }

    /**
     * @covers ::encodePassword
     */
    public function testEncodePassword()
    {
        self::assertSame('mzMEbtOdGC462vqQRa1nh9S7wyE=', $this->encoder->encodePassword('legacy'));
    }

    /**
     * @covers ::encodePassword
     */
    public function testEncodePasswordMaxLength()
    {
        $raw = str_repeat('*', Sha1PasswordEncoder::MAX_PASSWORD_LENGTH);

        try {
            $this->encoder->encodePassword($raw);
        }
        catch (\Exception $exception) {
            self::fail();
        }

        self::assertTrue(true);
    }

    /**
     * @covers ::encodePassword
     */
    public function testEncodePasswordTooLong()
    {
        $this->expectException(BadCredentialsException::class);

        $raw = str_repeat('*', Sha1PasswordEncoder::MAX_PASSWORD_LENGTH + 1);

        $this->encoder->encodePassword($raw);
    }

    /**
     * @covers ::isPasswordValid
     */
    public function testIsPasswordValid()
    {
        $encoded = 'mzMEbtOdGC462vqQRa1nh9S7wyE=';
        $valid   = 'legacy';
        $invalid = 'invalid';

        self::assertTrue($this->encoder->isPasswordValid($encoded, $valid));
        self::assertFalse($this->encoder->isPasswordValid($encoded, $invalid));
    }

    /**
     * @covers ::needsRehash
     */
    public function testNeedsRehash()
    {
        $encoded = 'mzMEbtOdGC462vqQRa1nh9S7wyE=';

        self::assertTrue($this->encoder->needsRehash($encoded));
    }
}
