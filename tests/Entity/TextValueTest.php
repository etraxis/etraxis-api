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
 * @coversDefaultClass \eTraxis\Entity\TextValue
 */
class TextValueTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $expected = str_pad(null, TextValue::MAX_VALUE, '_');
        $text     = new TextValue($expected);

        self::assertSame(md5($expected), $this->getProperty($text, 'token'));
        self::assertSame($expected, $text->value);
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = 'Lorem ipsum';
        $text     = new TextValue($expected);

        self::assertSame($expected, $text->jsonSerialize());
    }
}
