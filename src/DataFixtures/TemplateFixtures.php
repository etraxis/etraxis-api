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

namespace eTraxis\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use eTraxis\Entity\Template;

/**
 * Test fixtures for 'Template' entity.
 */
class TemplateFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            ProjectFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            'a' => ['task' => false, 'req' => true],
            'b' => ['task' => true,  'req' => true],
            'c' => ['task' => false, 'req' => false],
            'd' => ['task' => true,  'req' => false],
        ];

        foreach ($data as $ref => $isLocked) {

            /** @var \eTraxis\Entity\Project $project */
            $project = $this->getReference('project:' . $ref);

            $development = new Template($project);
            $support     = new Template($project);

            $development->name        = 'Development';
            $development->prefix      = 'task';
            $development->description = 'Development Task ' . mb_strtoupper($ref);
            $development->isLocked    = $isLocked['task'];

            $support->name        = 'Support';
            $support->prefix      = 'req';
            $support->description = 'Support Request ' . mb_strtoupper($ref);
            $support->criticalAge = 3;
            $support->frozenTime  = 7;
            $support->isLocked    = $isLocked['req'];

            $this->addReference('task:' . $ref, $development);
            $this->addReference('req:' . $ref, $support);

            $manager->persist($development);
            $manager->persist($support);
        }

        $manager->flush();
    }
}
