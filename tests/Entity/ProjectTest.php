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

use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\Project
 */
class ProjectTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $project = new Project();

        self::assertLessThanOrEqual(2, time() - $project->createdAt);
    }

    /**
     * @covers ::getters
     */
    public function testGroups()
    {
        $project = new Project();
        self::assertSame([], $project->groups);

        /** @var \Doctrine\Common\Collections\ArrayCollection $groups */
        $groups = $this->getProperty($project, 'groupsCollection');
        $groups->add('Group A');
        $groups->add('Group B');

        self::assertSame(['Group A', 'Group B'], $project->groups);
    }

    /**
     * @covers ::getters
     */
    public function testTemplates()
    {
        $project = new Project();
        self::assertSame([], $project->templates);

        /** @var \Doctrine\Common\Collections\ArrayCollection $templates */
        $templates = $this->getProperty($project, 'templatesCollection');
        $templates->add('Template A');
        $templates->add('Template B');

        self::assertSame(['Template A', 'Template B'], $project->templates);
    }
}
