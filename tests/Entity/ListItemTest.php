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

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\ListItem
 */
class ListItemTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        $field = new Field($state, FieldType::LIST);
        $this->setProperty($field, 'id', 2);

        $item = new ListItem($field);
        self::assertSame($field, $item->field);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid field type: number');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        $field = new Field($state, FieldType::NUMBER);
        $this->setProperty($field, 'id', 2);

        new ListItem($field);
    }
}
