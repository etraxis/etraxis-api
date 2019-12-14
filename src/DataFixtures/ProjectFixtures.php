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
use Doctrine\Common\Persistence\ObjectManager;
use eTraxis\Entity\Project;
use eTraxis\ReflectionTrait;

/**
 * Test fixtures for 'Project' entity.
 */
class ProjectFixtures extends Fixture
{
    use ReflectionTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'project:a' => [
                'name'        => 'Distinctio',
                'description' => 'Project A',
                'created'     => '2015-04-15',
                'suspended'   => true,
            ],

            'project:b' => [
                'name'        => 'Molestiae',
                'description' => 'Project B',
                'created'     => '2016-09-10',
            ],

            'project:c' => [
                'name'        => 'Excepturi',
                'description' => 'Project C',
                'created'     => '2017-04-11',
            ],

            'project:d' => [
                'name'        => 'Presto',
                'description' => 'Project D',
                'created'     => '2018-01-12',
            ],
        ];

        foreach ($data as $ref => $row) {

            $project = new Project();

            $project->name        = $row['name'];
            $project->description = $row['description'];
            $project->isSuspended = $row['suspended'] ?? false;

            $this->setProperty($project, 'createdAt', strtotime($row['created']));

            $this->addReference($ref, $project);

            $manager->persist($project);
        }

        $manager->flush();
    }
}
