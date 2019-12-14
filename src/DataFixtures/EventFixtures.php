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
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Application\Seconds;
use eTraxis\Entity\Event;
use eTraxis\ReflectionTrait;

/**
 * Test fixtures for 'Event' entity.
 */
class EventFixtures extends Fixture implements DependentFixtureInterface
{
    use ReflectionTrait;
    use UsersTrait;

    private const EVENT_TYPE      = 0;
    private const EVENT_USER      = 1;
    private const EVENT_DAY       = 2;
    private const EVENT_MIN       = 3;
    private const EVENT_PARAMETER = 4;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            StateFixtures::class,
            IssueFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task:%s:1' => [
                [EventType::ISSUE_CREATED,    $this->manager1,   0, 0,  'new'],
                [EventType::ISSUE_EDITED,     $this->manager1,   0, 5,  null],
                [EventType::FILE_ATTACHED,    $this->manager1,   0, 10, 0],
                [EventType::STATE_CHANGED,    $this->manager1,   0, 25, 'assigned'],
                [EventType::ISSUE_ASSIGNED,   $this->manager1,   0, 25, $this->developer1],
                [EventType::DEPENDENCY_ADDED, $this->manager1,   0, 30, 'task:%s:2'],
                [EventType::PUBLIC_COMMENT,   $this->manager1,   1, 0,  null],
                [EventType::ISSUE_CLOSED,     $this->developer1, 3, 0,  'completed'],
            ],

            'task:%s:2' => [
                [EventType::ISSUE_CREATED,   $this->manager2,   0, 0,   'new'],
                [EventType::STATE_CHANGED,   $this->manager1,   0, 10,  'assigned'],
                [EventType::ISSUE_ASSIGNED,  $this->manager1,   0, 10,  $this->developer3],
                [EventType::FILE_ATTACHED,   $this->manager1,   0, 15,  0],
                [EventType::FILE_ATTACHED,   $this->manager1,   0, 20,  1],
                [EventType::PUBLIC_COMMENT,  $this->manager1,   1, 0,   null],
                [EventType::ISSUE_CLOSED,    $this->developer3, 2, 35,  'completed'],
                [EventType::ISSUE_REOPENED,  $this->manager2,   2, 90,  'new'],
                [EventType::STATE_CHANGED,   $this->manager2,   2, 95,  'assigned'],
                [EventType::ISSUE_ASSIGNED,  $this->manager2,   2, 95,  $this->developer3],
                [EventType::FILE_DELETED,    $this->manager2,   2, 105, 1],
                [EventType::PRIVATE_COMMENT, $this->manager2,   2, 110, null],
                [EventType::FILE_ATTACHED,   $this->developer3, 3, 60,  2],
                [EventType::PUBLIC_COMMENT,  $this->developer3, 3, 65,  null],
            ],

            'task:%s:3' => [
                [EventType::ISSUE_CREATED,  $this->manager3,   0, 0, 'new'],
                [EventType::STATE_CHANGED,  $this->manager3,   0, 5, 'assigned'],
                [EventType::ISSUE_ASSIGNED, $this->manager3,   0, 5, $this->developer1],
                [EventType::ISSUE_CLOSED,   $this->developer1, 5, 0, 'completed'],
            ],

            'task:%s:4' => [
                [EventType::ISSUE_CREATED,  $this->developer1, 0, 0,   'new'],
                [EventType::ISSUE_CLOSED,   $this->manager2,   0, 135, 'duplicated'],
            ],

            'task:%s:5' => [
                [EventType::ISSUE_CREATED,  $this->manager3, 0, 0, 'new'],
            ],

            'task:%s:6' => [
                [EventType::ISSUE_CREATED,  $this->manager3, 0, 0, 'new'],
            ],

            'task:%s:7' => [
                [EventType::ISSUE_CREATED,  $this->developer2, 0, 0, 'new'],
                [EventType::STATE_CHANGED,  $this->manager2,   1, 0, 'assigned'],
                [EventType::ISSUE_ASSIGNED, $this->manager2,   1, 0, $this->developer2],
                [EventType::ISSUE_CLOSED,   $this->manager3,   2, 0, 'duplicated'],
            ],

            'task:%s:8' => [
                [EventType::ISSUE_CREATED,  $this->developer2, 0, 0, 'new'],
                [EventType::STATE_CHANGED,  $this->manager1,   3, 0, 'assigned'],
                [EventType::ISSUE_ASSIGNED, $this->manager1,   3, 0, $this->developer2],
            ],

            'req:%s:1' => [
                [EventType::ISSUE_CREATED,  $this->client1,  0, 0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->manager1, 0, 5, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->manager1, 0, 5, $this->support1],
                [EventType::ISSUE_CLOSED,   $this->support1, 2, 0, 'resolved'],
            ],

            'req:%s:2' => [
                [EventType::ISSUE_CREATED,  $this->client2,  0, 0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->support2, 0, 5, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->support2, 0, 5, $this->support2],
            ],

            'req:%s:3' => [
                [EventType::ISSUE_CREATED,  $this->client2,  0, 0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->support2, 0, 5, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->support2, 0, 5, $this->support2],
                [EventType::ISSUE_CLOSED,   $this->support2, 2, 0, 'resolved'],
            ],

            'req:%s:4' => [
                [EventType::ISSUE_CREATED,  $this->client3,  0, 0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->manager2, 1, 0, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->manager2, 1, 0, $this->support1],
            ],

            'req:%s:5' => [
                [EventType::ISSUE_CREATED,  $this->client2,  0, 0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->support3, 0, 5, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->support3, 0, 5, $this->support3],
            ],

            'req:%s:6' => [
                [EventType::ISSUE_CREATED,  $this->client1, 0, 0, 'submitted'],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $iref => $events) {

                /** @var \eTraxis\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));
                $manager->refresh($issue);

                foreach ($events as $index => $row) {

                    /** @var \eTraxis\Entity\User $user */
                    $user = $this->getReference($row[self::EVENT_USER][$pref]);

                    $timestamp = $issue->createdAt
                        + $row[self::EVENT_DAY] * Seconds::ONE_DAY
                        + $row[self::EVENT_MIN] * Seconds::ONE_MINUTE
                        + $index;

                    $event = new Event($row[self::EVENT_TYPE], $issue, $user);

                    $this->setProperty($event, 'createdAt', $timestamp);
                    $this->setProperty($issue, 'changedAt', $timestamp);

                    switch ($row[self::EVENT_TYPE]) {

                        case EventType::ISSUE_CREATED:
                        case EventType::ISSUE_REOPENED:
                        case EventType::ISSUE_CLOSED:
                        case EventType::STATE_CHANGED:

                            /** @var \eTraxis\Entity\State $entity */
                            $entity = $this->getReference(sprintf('%s:%s', $row[self::EVENT_PARAMETER], $pref));
                            $this->setProperty($event, 'parameter', $entity->id);

                            $issue->state = $entity;

                            if ($entity->type === StateType::FINAL) {
                                $this->setProperty($issue, 'closedAt', $timestamp);
                            }

                            break;

                        case EventType::ISSUE_ASSIGNED:

                            /** @var \eTraxis\Entity\User $entity */
                            $entity = $this->getReference($row[self::EVENT_PARAMETER][$pref]);
                            $this->setProperty($event, 'parameter', $entity->id);

                            break;

                        case EventType::DEPENDENCY_ADDED:
                        case EventType::DEPENDENCY_REMOVED:

                            /** @var \eTraxis\Entity\Issue $entity */
                            $entity = $this->getReference(sprintf($row[self::EVENT_PARAMETER], $pref));
                            $this->setProperty($event, 'parameter', $entity->id);

                            break;

                        default:

                            $this->setProperty($event, 'parameter', $row[self::EVENT_PARAMETER]);
                    }

                    $manager->persist($event);
                }

                $manager->persist($issue);
            }
        }

        $manager->flush();
    }
}
