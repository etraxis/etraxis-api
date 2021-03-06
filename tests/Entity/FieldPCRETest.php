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

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldPCRE
 */
class FieldPCRETest extends TestCase
{
    /**
     * @covers ::validate
     */
    public function testValidate()
    {
        $pcre = new FieldPCRE();

        $pcre->check = '(\d{3})-(\d{3})-(\d{4})';

        static::assertTrue($pcre->validate('123-456-7890'));
        static::assertFalse($pcre->validate('123-456-789'));
        static::assertFalse($pcre->validate('abc-def-ghij'));
        static::assertFalse($pcre->validate(''));
        static::assertFalse($pcre->validate(null));
    }

    /**
     * @covers ::transform
     */
    public function testTransform()
    {
        $expected = [
            '123-456-7890' => '(123) 456-7890',
            '123-456-789'  => '123-456-789',
            'abc-def-ghij' => 'abc-def-ghij',
            ''             => '',
            null           => '',
        ];

        $pcre = new FieldPCRE();

        $pcre->search  = '(\d{3})-(\d{3})-(\d{4})';
        $pcre->replace = '($1) $2-$3';

        foreach ($expected as $from => $to) {
            static::assertSame($to, $pcre->transform($from));
        }
    }

    /**
     * @covers ::transform
     */
    public function testTransform1()
    {
        $expected = '123-456-7890';

        $pcre = new FieldPCRE();

        $pcre->search = '(\d{3})-(\d{3})-(\d{4})';
        static::assertSame($expected, $pcre->transform($expected));

        $pcre->replace = '($1) $2-$3';
        static::assertNotSame($expected, $pcre->transform($expected));
    }

    /**
     * @covers ::transform
     */
    public function testTransform2()
    {
        $expected = '123-456-7890';

        $pcre = new FieldPCRE();

        $pcre->replace = '($1) $2-$3';
        static::assertSame($expected, $pcre->transform($expected));

        $pcre->search = '(\d{3})-(\d{3})-(\d{4})';
        static::assertNotSame($expected, $pcre->transform($expected));
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = [
            'check'   => null,
            'search'  => '(\d{3})-(\d{3})-(\d{4})',
            'replace' => '($1) $2-$3',
        ];

        $pcre = new FieldPCRE();

        $pcre->search  = '(\d{3})-(\d{3})-(\d{4})';
        $pcre->replace = '($1) $2-$3';

        static::assertSame($expected, $pcre->jsonSerialize());
    }
}
