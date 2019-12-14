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
use eTraxis\Entity\StateResponsibleGroup;

/**
 * Test fixtures for 'State' entity.
 */
class StateResponsibleGroupFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            GroupFixtures::class,
            StateFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task' => [

                'assigned:%s' => [
                    'developers:%s',
                ],
            ],

            'issue' => [

                'opened:%s' => [
                    'support:%s',
                ],
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            foreach ($data as $states) {

                foreach ($states as $sref => $groups) {

                    foreach ($groups as $gref) {

                        /** @var \eTraxis\Entity\State $state */
                        $state = $this->getReference(sprintf($sref, $pref));

                        /** @var \eTraxis\Entity\Group $group */
                        $group = $this->getReference(sprintf($gref, $pref));

                        $responsibleGroup = new StateResponsibleGroup($state, $group);
                        $manager->persist($responsibleGroup);
                    }
                }
            }
        }

        $manager->flush();
    }
}
