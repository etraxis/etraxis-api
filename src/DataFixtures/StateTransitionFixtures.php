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

namespace eTraxis\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Entity\StateGroupTransition;
use eTraxis\Entity\StateRoleTransition;

/**
 * Test fixtures for 'State' entity.
 */
class StateTransitionFixtures extends Fixture implements DependentFixtureInterface, FixtureInterface
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

                SystemRole::AUTHOR => [
                    'completed:%s' => 'new:%s',
                ],

                SystemRole::RESPONSIBLE => [
                    'assigned:%s' => 'completed:%s',
                ],

                'managers:%s' => [
                    'new:%s'       => 'assigned:%s',
                    'assigned:%s'  => 'duplicated:%s',
                    'completed:%s' => 'new:%s',
                ],
            ],

            'issue' => [

                SystemRole::AUTHOR => [
                    'submitted:%s' => 'resolved:%s',
                    'opened:%s'    => 'resolved:%s',
                    'resolved:%s'  => 'opened:%s',
                ],

                SystemRole::RESPONSIBLE => [
                    'opened:%s' => 'resolved:%s',
                ],

                'managers:%s' => [
                    'submitted:%s' => 'opened:%s',
                    'opened:%s'    => 'resolved:%s',
                ],

                'support:%s' => [
                    'submitted:%s' => 'opened:%s',
                ],
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            foreach ($data as $groups) {

                foreach ($groups as $gref => $transitions) {

                    foreach ($transitions as $from => $to) {

                        /** @var \eTraxis\Entity\State $fromState */
                        $fromState = $this->getReference(sprintf($from, $pref));

                        /** @var \eTraxis\Entity\State $toState */
                        $toState = $this->getReference(sprintf($to, $pref));

                        if (SystemRole::has($gref)) {
                            $roleTransition = new StateRoleTransition($fromState, $toState, $gref);
                            $manager->persist($roleTransition);
                        }
                        else {
                            /** @var \eTraxis\Entity\Group $group */
                            $group = $this->getReference(sprintf($gref, $pref));

                            $groupTransition = new StateGroupTransition($fromState, $toState, $group);
                            $manager->persist($groupTransition);
                        }
                    }
                }
            }
        }

        $manager->flush();
    }
}
