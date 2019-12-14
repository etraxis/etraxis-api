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

namespace eTraxis\Application\Dictionary;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Application\Dictionary\Timezone
 */
class TimezoneTest extends TestCase
{
    /**
     * @covers ::dictionary
     */
    public function testDictionary()
    {
        self::assertSame(timezone_identifiers_list(), Timezone::keys());
        self::assertSame(timezone_identifiers_list(), Timezone::values());
    }
}
