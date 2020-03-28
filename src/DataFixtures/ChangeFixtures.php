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
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Seconds;
use eTraxis\Entity\Change;
use eTraxis\Entity\Event;
use eTraxis\Entity\ListItem;
use eTraxis\Entity\StringValue;
use eTraxis\Entity\TextValue;

/**
 * Test fixtures for 'Change' entity.
 */
class ChangeFixtures extends Fixture implements DependentFixtureInterface, FixtureInterface
{
    private const EVENT_TYPE     = 0;
    private const EVENT_INDEX    = 1;
    private const CHANGED_FIELDS = 2;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            FieldFixtures::class,
            ListItemFixtures::class,
            IssueFixtures::class,
            EventFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task:%s:1' => [

                // Modified (first time).
                [EventType::ISSUE_EDITED, 0, [
                    'subject'         => ['Task 1', 'Development task 1'],
                    'new:%s:priority' => [3, 2],
                ]],
            ],

            'task:%s:2' => [

                // Reopened in 'New' state (first time).
                [EventType::ISSUE_REOPENED, 0, [
                    'new:%s:priority'    => [3, 1],
                    'new:%s:description' => [
                        'Velit voluptatem rerum nulla quos.',
                        'Velit voluptatem rerum nulla quos soluta excepturi omnis.',
                    ],
                ]],

                // Moved to 'Assigned' state (second time).
                [EventType::STATE_CHANGED, 1, [
                    'assigned:%s:due date' => [14, 7],
                ]],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $iref => $events) {

                /** @var \eTraxis\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));
                $manager->refresh($issue);

                foreach ($events as $row) {

                    /** @var Event[] $events */
                    $events = $manager->getRepository(Event::class)->findBy([
                        'type'  => $row[self::EVENT_TYPE],
                        'issue' => $issue,
                    ], [
                        'createdAt' => 'ASC',
                    ]);

                    $event = $events[$row[self::EVENT_INDEX]];

                    foreach ($row[self::CHANGED_FIELDS] as $fref => $values) {

                        $field    = null;
                        $oldValue = null;
                        $newValue = null;

                        if ($fref === 'subject') {

                            /** @var \eTraxis\Repository\Contracts\StringValueRepositoryInterface $repository */
                            $repository = $manager->getRepository(StringValue::class);

                            $oldValue = $repository->get($values[0])->id;
                            $newValue = $repository->get($values[1])->id;
                        }
                        else {

                            /** @var \eTraxis\Entity\Field $field */
                            $field = $this->getReference(sprintf($fref, $pref));

                            switch ($field->type) {

                                case FieldType::TEXT:

                                    /** @var \eTraxis\Repository\Contracts\TextValueRepositoryInterface $repository */
                                    $repository = $manager->getRepository(TextValue::class);

                                    $oldValue = $repository->get($values[0])->id;
                                    $newValue = $repository->get($values[1])->id;

                                    break;

                                case FieldType::LIST:

                                    /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $repository */
                                    $repository = $manager->getRepository(ListItem::class);

                                    $oldValue = $repository->findOneByValue($field, $values[0])->id;
                                    $newValue = $repository->findOneByValue($field, $values[1])->id;

                                    break;

                                case FieldType::DATE:

                                    $oldValue = $issue->createdAt + $values[0] * Seconds::ONE_DAY;
                                    $newValue = $issue->createdAt + $values[1] * Seconds::ONE_DAY;

                                    break;
                            }
                        }

                        $change = new Change($event, $field, $oldValue, $newValue);

                        $manager->persist($change);
                    }
                }
            }
        }

        $manager->flush();
    }
}
