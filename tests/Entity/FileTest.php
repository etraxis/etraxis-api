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
 * @coversDefaultClass \eTraxis\Entity\File
 */
class FileTest extends TestCase
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

        $event = new Event(EventType::FILE_ATTACHED, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $file = new File($event, 'example.csv', 2309, 'text/csv');

        static::assertSame($event, $file->event);
        static::assertSame('example.csv', $file->name);
        static::assertSame(2309, $file->size);
        static::assertSame('text/csv', $file->type);
        static::assertRegExp('/^([[:xdigit:]]{32})$/is', $file->uuid);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorFallback()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $event = new Event(EventType::FILE_ATTACHED, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $file = new File($event, 'example.csv', 2309, 'unknown/mime');

        static::assertSame($event, $file->event);
        static::assertSame('example.csv', $file->name);
        static::assertSame(2309, $file->size);
        static::assertSame('application/octet-stream', $file->type);
        static::assertRegExp('/^([[:xdigit:]]{32})$/is', $file->uuid);
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

        $event = new Event(EventType::FILE_ATTACHED, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $file = new File($event, 'example.csv', 2309, 'unknown/mime');

        static::assertSame($issue, $file->issue);
    }

    /**
     * @covers ::getters
     * @covers ::remove
     */
    public function testIsRemoved()
    {
        $user  = new User();
        $issue = new Issue($user);
        $event = new Event(EventType::FILE_ATTACHED, $issue, $user);

        $file = new File($event, 'example.csv', 2309, 'text/csv');
        static::assertFalse($file->isRemoved);

        $file->remove();
        static::assertTrue($file->isRemoved);
    }
}
