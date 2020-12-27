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

namespace eTraxis\Application\Dictionary;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Application\Dictionary\Timezone
 */
class TimezoneTest extends TestCase
{
    /**
     * @covers ::getCountries
     */
    public function testCountries()
    {
        $countries = Timezone::getCountries();

        static::assertArrayNotHasKey('??', $countries);
        static::assertArrayHasKey('NZ', $countries);
        static::assertSame('New Zealand', $countries['NZ']);
    }

    /**
     * @covers ::getCities
     */
    public function testCitiesAustralia()
    {
        $expected = [
            'Australia/Adelaide'    => 'Adelaide',
            'Australia/Brisbane'    => 'Brisbane',
            'Australia/Broken_Hill' => 'Broken Hill',
            'Australia/Currie'      => 'Currie',
            'Australia/Darwin'      => 'Darwin',
            'Australia/Eucla'       => 'Eucla',
            'Australia/Hobart'      => 'Hobart',
            'Australia/Lindeman'    => 'Lindeman',
            'Australia/Lord_Howe'   => 'Lord Howe',
            'Antarctica/Macquarie'  => 'Macquarie',
            'Australia/Melbourne'   => 'Melbourne',
            'Australia/Perth'       => 'Perth',
            'Australia/Sydney'      => 'Sydney',
        ];

        static::assertSame($expected, Timezone::getCities('AU'));
    }

    /**
     * @covers ::getCities
     */
    public function testCitiesArgentina()
    {
        $expected = [
            'America/Argentina/Buenos_Aires' => 'Buenos Aires',
            'America/Argentina/Catamarca'    => 'Catamarca',
            'America/Argentina/Cordoba'      => 'Cordoba',
            'America/Argentina/Jujuy'        => 'Jujuy',
            'America/Argentina/La_Rioja'     => 'La Rioja',
            'America/Argentina/Mendoza'      => 'Mendoza',
            'America/Argentina/Rio_Gallegos' => 'Rio Gallegos',
            'America/Argentina/Salta'        => 'Salta',
            'America/Argentina/San_Juan'     => 'San Juan',
            'America/Argentina/San_Luis'     => 'San Luis',
            'America/Argentina/Tucuman'      => 'Tucuman',
            'America/Argentina/Ushuaia'      => 'Ushuaia',
        ];

        static::assertSame($expected, Timezone::getCities('AR'));
    }

    /**
     * @covers ::dictionary
     */
    public function testDictionary()
    {
        static::assertSame(timezone_identifiers_list(), Timezone::keys());
        static::assertSame(timezone_identifiers_list(), Timezone::values());
    }
}
