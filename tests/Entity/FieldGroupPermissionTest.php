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

use eTraxis\Application\Dictionary\FieldPermission;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldGroupPermission
 */
class FieldGroupPermissionTest extends TestCase
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

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        $group = new Group($project);
        $this->setProperty($group, 'id', 5);

        $permission = new FieldGroupPermission($field, $group, FieldPermission::READ_WRITE);
        self::assertSame($field, $this->getProperty($permission, 'field'));
        self::assertSame($group, $this->getProperty($permission, 'group'));
        self::assertSame(FieldPermission::READ_WRITE, $this->getProperty($permission, 'permission'));
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

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 4);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 5);

        $group = new Group($project2);
        $this->setProperty($group, 'id', 6);
        $group->name = 'foo';

        new FieldGroupPermission($field, $group, FieldPermission::READ_WRITE);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionPermission()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown permission: bar');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        $group = new Group($project);
        $this->setProperty($group, 'id', 5);

        new FieldGroupPermission($field, $group, 'bar');
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = [
            'group'      => 5,
            'permission' => 'RW',
        ];

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        $group = new Group($project);
        $this->setProperty($group, 'id', 5);

        $permission = new FieldGroupPermission($field, $group, FieldPermission::READ_WRITE);

        self::assertSame($expected, $permission->jsonSerialize());
    }
}
