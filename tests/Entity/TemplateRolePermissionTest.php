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

use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Application\Dictionary\TemplatePermission;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\TemplateRolePermission
 */
class TemplateRolePermissionTest extends TestCase
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

        $permission = new TemplateRolePermission($template, SystemRole::AUTHOR, TemplatePermission::EDIT_ISSUES);
        self::assertSame($template, $this->getProperty($permission, 'template'));
        self::assertSame(SystemRole::AUTHOR, $this->getProperty($permission, 'role'));
        self::assertSame(TemplatePermission::EDIT_ISSUES, $this->getProperty($permission, 'permission'));
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionRole()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown system role: foo');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        new TemplateRolePermission($template, 'foo', TemplatePermission::EDIT_ISSUES);
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

        new TemplateRolePermission($template, SystemRole::AUTHOR, 'bar');
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = [
            'role'       => 'author',
            'permission' => 'issue.edit',
        ];

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $permission = new TemplateRolePermission($template, SystemRole::AUTHOR, TemplatePermission::EDIT_ISSUES);

        self::assertSame($expected, $permission->jsonSerialize());
    }
}
