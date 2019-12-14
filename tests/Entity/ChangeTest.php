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

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\Change
 */
class ChangeTest extends TestCase
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

        $event = new Event(EventType::ISSUE_EDITED, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        $this->setProperty($field, 'id', 4);

        $change = new Change($event, $field, null, 100);

        self::assertSame($event, $change->event);
        self::assertSame($field, $change->field);
        self::assertNull($change->oldValue);
        self::assertSame(100, $change->newValue);
    }
}
