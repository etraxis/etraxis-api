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
use eTraxis\Entity\DecimalValue;
use eTraxis\Entity\Event;
use eTraxis\Entity\FieldValue;
use eTraxis\Entity\ListItem;
use eTraxis\Entity\StringValue;
use eTraxis\Entity\TextValue;
use eTraxis\ReflectionTrait;

/**
 * Test fixtures for 'FieldValue' entity.
 */
class FieldValueFixtures extends Fixture implements DependentFixtureInterface, FixtureInterface
{
    use ReflectionTrait;

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
                EventType::ISSUE_CREATED => [
                    'new:%s:priority'    => 2,
                    'new:%s:description' => 'Quas sunt reprehenderit vero accusantium.',
                    'new:%s:error'       => false,
                ],
                EventType::STATE_CHANGED => [
                    'assigned:%s:due date' => null,
                ],
                EventType::ISSUE_CLOSED  => [
                    'completed:%s:commit id'     => null,
                    'completed:%s:delta'         => 5173,
                    'completed:%s:effort'        => 1440,       // 24 hours
                    'completed:%s:test coverage' => '98.49',
                ],
            ],

            'task:%s:2' => [
                EventType::ISSUE_CREATED  => [
                    'new:%s:priority'    => 1,
                    'new:%s:description' => 'Velit voluptatem rerum nulla quos soluta excepturi omnis.',
                    'new:%s:error'       => true,
                ],
                EventType::STATE_CHANGED  => [
                    'assigned:%s:due date' => 7,
                ],
                EventType::ISSUE_CLOSED   => [
                    'completed:%s:commit id'     => '940059027173b8e8e1e3e874681f012f1f3bcf1d',
                    'completed:%s:delta'         => 1,
                    'completed:%s:effort'        => 80,         // 1:20
                    'completed:%s:test coverage' => null,
                ],
                EventType::ISSUE_REOPENED => [
                    'new:%s:new feature' => false,
                ],
            ],

            'task:%s:3' => [
                EventType::ISSUE_CREATED => [
                    'new:%s:priority'    => 2,
                    'new:%s:description' => 'Et nostrum et ut in ullam voluptatem dolorem et.',
                    'new:%s:new feature' => true,
                ],
                EventType::STATE_CHANGED => [
                    'assigned:%s:due date' => null,
                ],
                EventType::ISSUE_CLOSED  => [
                    'completed:%s:commit id'     => '067d9eebe965d2451cd3bd9333e46f38f3ec94c7',
                    'completed:%s:delta'         => 7403,
                    'completed:%s:effort'        => 2250,       // 37:30
                    'completed:%s:test coverage' => '99.05',
                ],
            ],

            'task:%s:4' => [
                EventType::ISSUE_CREATED => [
                    'new:%s:priority'    => 2,
                    'new:%s:description' => 'Omnis id quos recusandae provident.',
                    'new:%s:new feature' => true,
                ],
                EventType::ISSUE_CLOSED  => [
                    'duplicated:%s:task id' => 'task:%s:3',
                ],
            ],

            'task:%s:5' => [
                EventType::ISSUE_CREATED => [
                    'new:%s:priority'    => 2,
                    'new:%s:description' => null,
                    'new:%s:new feature' => false,
                ],
            ],

            'task:%s:6' => [
                EventType::ISSUE_CREATED => [
                    'new:%s:priority'    => 1,
                    'new:%s:description' => 'Voluptatum qui ratione sed molestias quo aliquam.',
                    'new:%s:new feature' => true,
                ],
            ],

            'task:%s:7' => [
                EventType::ISSUE_CREATED => [
                    'new:%s:priority'    => 2,
                    'new:%s:description' => 'Sapiente et velit aut minus sequi et.',
                    'new:%s:new feature' => true,
                ],
                EventType::STATE_CHANGED => [
                    'assigned:%s:due date' => 15,     // 1 day after creation + 14 days due
                ],
                EventType::ISSUE_CLOSED  => [
                    'duplicated:%s:issue id' => 'task:%s:6',
                ],
            ],

            'task:%s:8' => [
                EventType::ISSUE_CREATED => [
                    'new:%s:priority'    => 1,
                    'new:%s:description' => 'Esse labore et ducimus consequuntur labore voluptatem atque.',
                    'new:%s:new feature' => false,
                ],
                EventType::STATE_CHANGED => [
                    'assigned:%s:due date' => 6,        // 3 days after creation + 3 days due
                ],
            ],

            'req:%s:1' => [
                EventType::ISSUE_CREATED => [
                    'submitted:%s:details' => 'Expedita ullam iste omnis natus veritatis sint temporibus provident velit veniam provident rerum doloremque autem repellat est in sed.',
                ],
            ],

            'req:%s:2' => [
                EventType::ISSUE_CREATED => [
                    'submitted:%s:details' => 'Laborum sed saepe esse distinctio inventore nulla ipsam qui est qui laborum iste iure natus ea saepe qui recusandae similique est quia sed.',
                ],
            ],

            'req:%s:3' => [
                EventType::ISSUE_CREATED => [
                    'submitted:%s:details' => 'Est ut inventore omnis doloribus et corporis adipisci ut est rem sapiente numquam dolor voluptatibus quibusdam quo voluptates ab doloribus illum recusandae libero accusantium. Animi rem ut ut aperiam laborum sapiente quis dicta qui nostrum occaecati commodi non.',
                ],
            ],

            'req:%s:4' => [
                EventType::ISSUE_CREATED => [
                    'submitted:%s:details' => 'Distinctio maiores placeat quo cupiditate est autem excepturi cumque et dolorum qui rem minima ab enim dolor voluptas odio fugiat ea aspernatur voluptas enim. Sint dolor asperiores et facilis excepturi quasi perspiciatis ut ut reprehenderit aspernatur repellat adipisci ut aut laudantium cumque dicta ea non.',
                ],
            ],

            'req:%s:5' => [
                EventType::ISSUE_CREATED => [
                    'submitted:%s:details' => 'Sapiente cum placeat consequatur repellat est aliquid ut sed praesentium aliquid dolorum cumque quas qui maiores consequatur nihil commodi iure architecto molestias libero. Dicta id illum officiis ut numquam et et quisquam libero voluptatem ad accusamus aspernatur est consequatur et minima reiciendis repellat culpa.',
                ],
            ],

            'req:%s:6' => [
                EventType::ISSUE_CREATED => [
                    'submitted:%s:details' => 'Quis quaerat ut corrupti vitae sed rerum voluptate consequatur odio molestiae voluptatibus esse nostrum sunt perspiciatis in fuga est vitae enim. Voluptas distinctio enim ullam iusto voluptate vitae voluptatem ipsa placeat asperiores molestiae eveniet expedita at officiis incidunt amet.',
                ],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $iref => $event_types) {

                foreach ($event_types as $event_type => $fields) {

                    /** @var \eTraxis\Entity\Issue $issue */
                    $issue = $this->getReference(sprintf($iref, $pref));

                    /** @var Event $event */
                    [$event] = $manager->getRepository(Event::class)->findBy([
                        'type'  => $event_type,
                        'issue' => $issue,
                    ]);

                    foreach ($fields as $fref => $vref) {

                        /** @var \eTraxis\Entity\Field $field */
                        $field = $this->getReference(sprintf($fref, $pref));

                        $value = $vref;

                        if ($value !== null) {

                            switch ($field->type) {

                                case FieldType::CHECKBOX:
                                    $value = $vref ? 1 : 0;
                                    break;

                                case FieldType::DATE:
                                    $value = $issue->createdAt + $vref * Seconds::ONE_DAY;
                                    break;

                                case FieldType::DECIMAL:
                                    /** @var \eTraxis\Repository\Contracts\DecimalValueRepositoryInterface $repository */
                                    $repository = $manager->getRepository(DecimalValue::class);
                                    $value      = $repository->get($vref)->id;
                                    break;

                                case FieldType::DURATION:
                                    $value = $vref;
                                    break;

                                case FieldType::ISSUE:
                                    /** @var \eTraxis\Entity\Issue $entity */
                                    $entity = $this->getReference(sprintf($vref, $pref));
                                    $value  = $entity->id;
                                    break;

                                case FieldType::LIST:
                                    /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $repository */
                                    $repository = $manager->getRepository(ListItem::class);
                                    $value      = $repository->findOneByValue($field, $vref)->id;
                                    break;

                                case FieldType::NUMBER:
                                    break;

                                case FieldType::STRING:
                                    /** @var \eTraxis\Repository\Contracts\StringValueRepositoryInterface $repository */
                                    $repository = $manager->getRepository(StringValue::class);
                                    $value      = $repository->get($vref)->id;
                                    break;

                                case FieldType::TEXT:
                                    /** @var \eTraxis\Repository\Contracts\TextValueRepositoryInterface $repository */
                                    $repository = $manager->getRepository(TextValue::class);
                                    $value      = $repository->get($vref)->id;
                                    break;
                            }
                        }

                        $fieldValue = new FieldValue($issue, $field, $value);

                        $this->setProperty($fieldValue, 'createdAt', $event->createdAt);

                        $manager->persist($fieldValue);
                    }

                    $manager->persist($issue);
                }
            }
        }

        $manager->flush();
    }
}
