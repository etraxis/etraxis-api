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

namespace eTraxis\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \eTraxis\Console\JsConstCommand
 */
class JsConstCommandTest extends WebTestCase
{
    /**
     * @covers ::configure
     * @covers ::execute
     */
    public function testJsConst()
    {
        static::bootKernel();

        $application = new Application(self::$kernel);
        $application->add(new JsConstCommand());

        $commandTester = new CommandTester($application->find('etraxis:js-const'));
        $commandTester->execute([]);

        self::assertSame('[OK] Successfully exported.', trim($commandTester->getDisplay()));
    }
}
