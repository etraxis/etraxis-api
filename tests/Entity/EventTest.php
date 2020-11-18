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

use eTraxis\Application\Dictionary\EventType;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\Event
 */
class EventTest extends TestCase
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

        $event = new Event(EventType::ISSUE_ASSIGNED, $issue, $user, $user->id);

        self::assertSame(EventType::ISSUE_ASSIGNED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertSame(1, $event->parameter);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
    }
}
