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
 * @coversDefaultClass \eTraxis\Entity\Template
 */
class TemplateTest extends TestCase
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
        self::assertSame($project, $this->getProperty($template, 'project'));
    }

    /**
     * @covers ::getters
     */
    public function testInitialState()
    {
        $template = new Template(new Project());
        self::assertNull($template->initialState);

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 1);

        $intermediate = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($initial, 'id', 2);

        $final = new State($template, StateType::FINAL);
        $this->setProperty($initial, 'id', 3);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'statesCollection');

        $states->add($intermediate);
        $states->add($final);
        self::assertNull($template->initialState);

        $states->add($initial);
        self::assertSame($initial, $template->initialState);
    }

    /**
     * @covers ::getters
     */
    public function testStates()
    {
        $template = new Template(new Project());
        self::assertSame([], $template->states);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'statesCollection');
        $states->add('State A');
        $states->add('State B');

        self::assertSame(['State A', 'State B'], $template->states);
    }

    /**
     * @covers ::getters
     */
    public function testRolePermissions()
    {
        $template = new Template(new Project());
        self::assertSame([], $template->rolePermissions);

        /** @var \Doctrine\Common\Collections\Collection $permissions */
        $permissions = $this->getProperty($template, 'rolePermissionsCollection');
        $permissions->add('Role permission A');
        $permissions->add('Role permission B');

        self::assertSame(['Role permission A', 'Role permission B'], $template->rolePermissions);
    }

    /**
     * @covers ::getters
     */
    public function testGroupPermissions()
    {
        $template = new Template(new Project());
        self::assertSame([], $template->groupPermissions);

        /** @var \Doctrine\Common\Collections\Collection $permissions */
        $permissions = $this->getProperty($template, 'groupPermissionsCollection');
        $permissions->add('Group permission A');
        $permissions->add('Group permission B');

        self::assertSame(['Group permission A', 'Group permission B'], $template->groupPermissions);
    }
}
