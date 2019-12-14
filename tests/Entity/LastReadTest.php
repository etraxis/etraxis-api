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
 * @coversDefaultClass \eTraxis\Entity\LastRead
 */
class LastReadTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $read = new LastRead($issue, $user);

        self::assertSame($issue, $read->issue);
        self::assertSame($user, $read->user);
        self::assertLessThanOrEqual(2, time() - $read->readAt);
    }

    /**
     * @covers ::touch
     */
    public function testTouch()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $read = new LastRead($issue, $user);

        $this->setProperty($read, 'readAt', 0);
        self::assertGreaterThan(2, time() - $read->readAt);

        $read->touch();
        self::assertLessThanOrEqual(2, time() - $read->readAt);
    }
}
