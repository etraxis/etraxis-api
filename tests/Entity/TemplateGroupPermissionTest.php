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

use eTraxis\Application\Dictionary\TemplatePermission;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\TemplateGroupPermission
 */
class TemplateGroupPermissionTest extends TestCase
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

        $group = new Group($project);
        $this->setProperty($group, 'id', 3);

        $permission = new TemplateGroupPermission($template, $group, TemplatePermission::EDIT_ISSUES);
        self::assertSame($template, $this->getProperty($permission, 'template'));
        self::assertSame($group, $this->getProperty($permission, 'group'));
        self::assertSame(TemplatePermission::EDIT_ISSUES, $this->getProperty($permission, 'permission'));
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

        $group = new Group($project2);
        $this->setProperty($group, 'id', 4);
        $group->name = 'foo';

        new TemplateGroupPermission($template, $group, TemplatePermission::EDIT_ISSUES);
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

        $group = new Group($project);
        $this->setProperty($group, 'id', 3);

        new TemplateGroupPermission($template, $group, 'bar');
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = [
            'group'      => 3,
            'permission' => 'issue.edit',
        ];

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $group = new Group($project);
        $this->setProperty($group, 'id', 3);

        $permission = new TemplateGroupPermission($template, $group, TemplatePermission::EDIT_ISSUES);

        self::assertSame($expected, $permission->jsonSerialize());
    }
}
