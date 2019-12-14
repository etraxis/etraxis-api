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
 * @coversDefaultClass \eTraxis\Entity\Dependency
 */
class DependencyTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue1 = new Issue($user);
        $this->setProperty($issue1, 'id', 2);

        $issue2 = new Issue($user);
        $this->setProperty($issue2, 'id', 3);

        $dependency = new Dependency($issue1, $issue2);

        self::assertSame($issue1, $dependency->issue);
        self::assertSame($issue2, $dependency->dependency);
    }
}
