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

use eTraxis\Application\Dictionary\StateType;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\StateResponsibleGroup
 */
class StateResponsibleGroupTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 3);

        $group = new Group($project);
        $this->setProperty($group, 'id', 4);

        $transition = new StateResponsibleGroup($state, $group);
        self::assertSame($state, $this->getProperty($transition, 'state'));
        self::assertSame($group, $this->getProperty($transition, 'group'));
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionGroup()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown group: foo');

        $project1 = new Project();
        $this->setProperty($project1, 'id', 1);

        $project2 = new Project();
        $this->setProperty($project2, 'id', 2);

        $template = new Template($project1);
        $this->setProperty($template, 'id', 3);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 4);

        $group = new Group($project2);
        $this->setProperty($group, 'id', 5);
        $group->name = 'foo';

        new StateResponsibleGroup($state, $group);
    }
}
