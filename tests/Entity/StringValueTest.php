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

use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\StringValue
 */
class StringValueTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $expected = str_pad(null, 250, '_');
        $string   = new StringValue($expected);

        self::assertSame(md5($expected), $this->getProperty($string, 'token'));
        self::assertSame($expected, $string->value);
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = 'Lorem ipsum';
        $string   = new StringValue($expected);

        self::assertSame($expected, $string->jsonSerialize());
    }
}
