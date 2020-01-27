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
use Doctrine\Persistence\ObjectManager;
use eTraxis\Application\Dictionary\StateResponsible;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\State;

/**
 * Test fixtures for 'State' entity.
 */
class StateFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            TemplateFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task' => [
                'Assigned'   => [
                    'type'        => StateType::INTERMEDIATE,
                    'responsible' => StateResponsible::ASSIGN,
                ],
                'New'        => [
                    'type'        => StateType::INITIAL,
                    'responsible' => StateResponsible::REMOVE,
                    'next'        => 'assigned',
                ],
                'Completed'  => [
                    'type' => StateType::FINAL,
                ],
                'Duplicated' => [
                    'type' => StateType::FINAL,
                ],
            ],

            'req' => [
                'Submitted' => [
                    'type'        => StateType::INITIAL,
                    'responsible' => StateResponsible::KEEP,
                ],
                'Opened'    => [
                    'type'        => StateType::INTERMEDIATE,
                    'responsible' => StateResponsible::ASSIGN,
                ],
                'Resolved'  => [
                    'type' => StateType::FINAL,
                ],
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            foreach ($data as $tref => $states) {

                /** @var \eTraxis\Entity\Template $template */
                $template = $this->getReference(sprintf('%s:%s', $tref, $pref));

                foreach ($states as $name => $row) {

                    $state = new State($template, $pref === 'd' ? StateType::INTERMEDIATE : $row['type']);

                    $state->name        = $name;
                    $state->responsible = $row['responsible'] ?? StateResponsible::REMOVE;

                    if ($row['next'] ?? null) {
                        $state->nextState = $this->getReference(sprintf('%s:%s', $row['next'], $pref));
                    }

                    $this->addReference(sprintf('%s:%s', mb_strtolower($name), $pref), $state);

                    $manager->persist($state);
                }
            }
        }

        $manager->flush();
    }
}
