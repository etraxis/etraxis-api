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
 * @coversDefaultClass \eTraxis\Entity\StateGroupTransition
 */
class StateGroupTransitionTest extends TestCase
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

        $from = new State($template, StateType::INITIAL);
        $this->setProperty($from, 'id', 3);

        $to = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 4);

        $group = new Group($project);
        $this->setProperty($group, 'id', 5);

        $transition = new StateGroupTransition($from, $to, $group);
        static::assertSame($from, $this->getProperty($transition, 'fromState'));
        static::assertSame($to, $this->getProperty($transition, 'toState'));
        static::assertSame($group, $this->getProperty($transition, 'group'));
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionStates()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('States must belong the same template.');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template1 = new Template($project);
        $this->setProperty($template1, 'id', 2);

        $template2 = new Template($project);
        $this->setProperty($template2, 'id', 3);

        $from = new State($template1, StateType::INITIAL);
        $this->setProperty($from, 'id', 4);

        $to = new State($template2, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 5);

        $group = new Group($project);
        $this->setProperty($group, 'id', 6);

        new StateGroupTransition($from, $to, $group);
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

        $from = new State($template, StateType::INITIAL);
        $this->setProperty($from, 'id', 4);

        $to = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 5);

        $group = new Group($project2);
        $this->setProperty($group, 'id', 6);
        $group->name = 'foo';

        new StateGroupTransition($from, $to, $group);
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = [
            'state' => 4,
            'group' => 5,
        ];

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $from = new State($template, StateType::INITIAL);
        $this->setProperty($from, 'id', 3);

        $to = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 4);

        $group = new Group($project);
        $this->setProperty($group, 'id', 5);

        $transition = new StateGroupTransition($from, $to, $group);

        static::assertSame($expected, $transition->jsonSerialize());
    }
}
