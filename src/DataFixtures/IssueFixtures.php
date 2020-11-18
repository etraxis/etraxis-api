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
use eTraxis\Application\Seconds;
use eTraxis\Entity\Issue;
use eTraxis\ReflectionTrait;

/**
 * Test fixtures for 'Issue' entity.
 */
class IssueFixtures extends Fixture implements DependentFixtureInterface, FixtureInterface
{
    use ReflectionTrait;
    use UsersTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            TemplateFixtures::class,
            StateFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task1' => [
                'subject'     => 'Development task 1',
                'author'      => $this->manager1,
                'responsible' => null,
            ],

            'task2' => [
                'subject'     => 'Development task 2',
                'author'      => $this->manager2,
                'responsible' => $this->developer3,
            ],

            'task3' => [
                'subject'     => 'Development task 3',
                'author'      => $this->developer3,
                'responsible' => null,
            ],

            'task4' => [
                'subject'     => 'Development task 4',
                'author'      => $this->developer1,
                'responsible' => null,
            ],

            'task5' => [
                'subject'     => 'Development task 5',
                'author'      => $this->manager3,
                'responsible' => null,
                'suspended'   => true,
                'origin'      => 'task:%s:3',
            ],

            'task6' => [
                'subject'     => 'Development task 6',
                'author'      => $this->manager3,
                'responsible' => null,
            ],

            'task7' => [
                'subject'     => 'Development task 7',
                'author'      => $this->developer2,
                'responsible' => null,
            ],

            'task8' => [
                'subject'     => 'Development task 8',
                'author'      => $this->developer2,
                'responsible' => $this->support2,
            ],

            'req1' => [
                'subject'     => 'Support request 1',
                'author'      => $this->client1,
                'responsible' => null,
            ],

            'req2' => [
                'subject'     => 'Support request 2',
                'author'      => $this->client2,
                'responsible' => $this->support2,
            ],

            'req3' => [
                'subject'     => 'Support request 3',
                'author'      => $this->client2,
                'responsible' => null,
            ],

            'req4' => [
                'subject'     => 'Support request 4',
                'author'      => $this->client3,
                'responsible' => $this->support1,
            ],

            'req5' => [
                'subject'     => 'Support request 5',
                'author'      => $this->client2,
                'responsible' => $this->support3,
                'suspended'   => true,
            ],

            'req6' => [
                'subject'     => 'Support request 6',
                'author'      => $this->client1,
                'responsible' => null,
            ],
        ];

        $sequence = [
            // Project A started
            'task:a:1' => '2015-04-15',
            'task:a:2' => '2015-04-21',
            'task:a:3' => '2015-05-03',
            'task:a:4' => '2015-06-19',
            'task:a:5' => '2015-07-17',
            'task:a:6' => '2015-08-24',
            'req:a:1'  => '2015-10-31',
            'req:a:2'  => '2015-11-02',
            'task:a:7' => '2015-12-04',
            'task:a:8' => '2015-12-18',
            'req:a:3'  => '2016-01-25',
            'req:a:4'  => '2016-02-21',
            'req:a:5'  => '2016-03-05',

            // Project B started
            'task:b:1' => '2016-09-12',
            'task:b:2' => '2016-09-29',
            'task:b:3' => '2016-10-13',
            'req:a:6'  => '2016-10-31',
            'task:b:4' => '2016-11-06',
            'task:b:5' => '2016-12-02',
            'task:b:6' => '2017-01-26',
            'task:b:7' => '2017-02-08',
            'req:b:1'  => '2017-02-21',
            'req:b:2'  => '2017-03-20',
            'req:b:3'  => '2017-04-03',
            'task:b:8' => '2017-04-24',
            'req:b:4'  => '2017-05-18',
            'req:b:5'  => '2017-05-30',

            // Project C started
            'task:c:1' => '2017-04-14',
            'task:c:2' => '2017-04-23',
            'req:b:6'  => '2017-04-30',
            'task:c:3' => '2017-05-12',
            'task:c:4' => '2017-06-01',
            'task:c:5' => '2017-06-18',
            'task:c:6' => '2017-07-05',
            'req:c:1'  => '2017-07-24',
            'req:c:2'  => '2017-08-10',
            'task:c:7' => '2017-08-15',
            'req:c:3'  => '2017-09-06',
            'task:c:8' => '2017-09-17',
            'req:c:4'  => '2017-09-23',
            'req:c:5'  => '2017-10-11',
            'req:c:6'  => '2017-10-14',
        ];

        foreach ($sequence as $ref => $date) {

            [$tref, $pref, $iref] = explode(':', $ref);

            $row = $data[$tref . $iref];

            /** @var \eTraxis\Entity\Template $template */
            $template = $this->getReference(sprintf('%s:%s', $tref, $pref));

            /** @var \eTraxis\Entity\User $author */
            $author = $this->getReference($row['author'][$pref]);

            $issue = new Issue($author);

            $issue->subject     = $row['subject'];
            $issue->state       = $template->initialState;
            $issue->responsible = $row['responsible'] && $row['responsible'][$pref] ? $this->getReference($row['responsible'][$pref]) : null;

            if ($row['origin'] ?? false) {
                /** @var Issue $origin */
                $origin = $this->getReference(sprintf($row['origin'], $pref));
                $this->setProperty($issue, 'origin', $origin);
            }

            $createdAt = strtotime($date . ' 09:00:00');

            $this->setProperty($issue, 'createdAt', $createdAt);
            $this->setProperty($issue, 'changedAt', $createdAt);

            if ($row['suspended'] ?? false) {
                $issue->suspend(time() + Seconds::ONE_DAY);
            }

            $this->addReference($ref, $issue);

            $manager->persist($issue);
        }

        $manager->flush();
    }
}
