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
use eTraxis\Entity\Watcher;

/**
 * Test fixtures for 'Watcher' entity.
 */
class WatcherFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            IssueFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            'fdooley@example.com' => [
                'task:%s:1',
                'task:%s:2',
                'task:%s:3',
                'task:%s:5',
                'task:%s:6',
                'req:%s:2',
            ],
            'tmarquardt@example.com' => [
                'req:%s:1',
                'req:%s:2',
                'req:%s:3',
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $uref => $issues) {

                /** @var \eTraxis\Entity\User $user */
                $user = $this->getReference('user:' . $uref);

                foreach ($issues as $iref) {

                    /** @var \eTraxis\Entity\Issue $issue */
                    $issue = $this->getReference(sprintf($iref, $pref));
                    $manager->refresh($issue);

                    $watcher = new Watcher($issue, $user);

                    $manager->persist($watcher);
                }
            }
        }

        $manager->flush();
    }
}
