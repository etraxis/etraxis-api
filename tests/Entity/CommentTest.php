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
 * @coversDefaultClass \eTraxis\Entity\Comment
 */
class CommentTest extends TestCase
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

        $event = new Event(EventType::PUBLIC_COMMENT, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $comment = new Comment($event);

        static::assertSame($event, $comment->event);
    }

    /**
     * @covers ::getters
     */
    public function testIssue()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $event = new Event(EventType::PUBLIC_COMMENT, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $comment = new Comment($event);

        static::assertSame($issue, $comment->issue);
    }
}
