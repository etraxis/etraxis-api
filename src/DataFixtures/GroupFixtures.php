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
use eTraxis\Entity\Group;

/**
 * Test fixtures for 'Group' entity.
 */
class GroupFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            ProjectFixtures::class,
            UserFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            'managers'   => 'Managers',
            'developers' => 'Developers',
            'clients'    => 'Clients',
            'support'    => 'Support Engineers',
        ];

        $members = [

            'a' => [
                'managers'   => [
                    'ldoyle@example.com',
                    'dorcas.ernser@example.com',
                    'berenice.oconnell@example.com',
                    'dangelo.hill@example.com',
                ],
                'developers' => [
                    'fdooley@example.com',
                    'labshire@example.com',
                    'dquigley@example.com',
                    'christy.mcdermott@example.com',
                ],
                'clients'    => [
                    'lucas.oconnell@example.com',
                    'clegros@example.com',
                    'jmueller@example.com',
                    'hstroman@example.com',
                ],
                'support'    => [
                    'tmarquardt@example.com',
                    'bkemmer@example.com',
                    'cbatz@example.com',
                    'kschultz@example.com',
                    'nhills@example.com',
                    'jkiehn@example.com',
                ],
            ],

            'b' => [
                'managers'   => [
                    'ldoyle@example.com',
                    'dorcas.ernser@example.com',
                    'carolyn.hill@example.com',
                    'emmanuelle.bartell@example.com',
                ],
                'developers' => [
                    'fdooley@example.com',
                    'labshire@example.com',
                    'akoepp@example.com',
                    'amarvin@example.com',
                    'jkiehn@example.com',
                ],
                'clients'    => [
                    'lucas.oconnell@example.com',
                    'clegros@example.com',
                    'dtillman@example.com',
                    'aschinner@example.com',
                ],
                'support'    => [
                    'tmarquardt@example.com',
                    'bkemmer@example.com',
                    'kbahringer@example.com',
                    'vparker@example.com',
                    'nhills@example.com',
                ],
            ],

            'c' => [
                'managers'   => [
                    'ldoyle@example.com',
                    'berenice.oconnell@example.com',
                    'carolyn.hill@example.com',
                    'jgoodwin@example.com',
                    'jkiehn@example.com',
                ],
                'developers' => [
                    'fdooley@example.com',
                    'dquigley@example.com',
                    'akoepp@example.com',
                    'mbogisich@example.com',
                    'nhills@example.com',
                ],
                'clients'    => [
                    'lucas.oconnell@example.com',
                    'jmueller@example.com',
                    'dtillman@example.com',
                    'dmurazik@example.com',
                ],
                'support'    => [
                    'tmarquardt@example.com',
                    'cbatz@example.com',
                    'kbahringer@example.com',
                    'tbuckridge@example.com',
                ],
            ],

            'd' => [
                'managers'   => [
                    'ldoyle@example.com',
                ],
                'developers' => [],
                'clients'    => [],
                'support'    => [],
            ],
        ];

        $globals = [
            'staff'   => [],
            'clients' => [],
        ];

        // Project groups.

        foreach ($members as $ref => $row) {

            $globals['staff']   = array_merge($globals['staff'], $row['managers'], $row['developers'], $row['support']);
            $globals['clients'] = array_merge($globals['clients'], $row['clients']);

            /** @var \eTraxis\Entity\Project $project */
            $project = $this->getReference('project:' . $ref);

            foreach ($row as $name => $emails) {

                $group = new Group($project);

                $group->name        = $data[$name];
                $group->description = sprintf('%s %s', $data[$name], mb_strtoupper($ref));

                $this->addReference(sprintf('%s:%s', $name, $ref), $group);

                foreach ($emails as $email) {
                    /** @var \eTraxis\Entity\User $user */
                    $user = $this->getReference('user:' . $email);
                    $group->addMember($user);
                }

                $manager->persist($group);
            }
        }

        // Global groups.

        foreach ($globals as $ref => $row) {

            $group = new Group();

            $group->name = 'Company ' . ucwords($ref);

            $this->addReference($ref, $group);

            $members = array_unique($row);

            foreach ($members as $email) {
                /** @var \eTraxis\Entity\User $user */
                $user = $this->getReference('user:' . $email);
                $group->addMember($user);
            }

            $manager->persist($group);
        }

        $manager->flush();
    }
}
