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
use eTraxis\Entity\Event;
use eTraxis\Entity\File;
use eTraxis\ReflectionTrait;

/**
 * Test fixtures for 'File' entity.
 */
class FileFixtures extends Fixture implements DependentFixtureInterface, FixtureInterface
{
    use ReflectionTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
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
                [
                    'Inventore.pdf',
                    175971,     // 171.85 KB
                    'application/pdf',
                    false,
                ],
            ],

            'task:%s:2' => [
                [
                    'Beatae nesciunt natus suscipit iure assumenda commodi.docx',
                    217948,     // 212.84 KB
                    'application/vnd\.ms-word',
                    false,
                ],
                [
                    'Possimus sapiente.pdf',
                    10753,      // 10.50 KB
                    'application/pdf',
                    true,
                ],
                [
                    'Nesciunt nulla sint amet.xslx',
                    6037279,    // 5895.78 KB
                    'application/vnd\.ms-excel',
                    false,
                ],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $iref => $files) {

                /** @var \eTraxis\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));
                $manager->refresh($issue);

                /** @var Event[] $events */
                $events = $manager->getRepository(Event::class)->findBy([
                    'type'  => EventType::FILE_ATTACHED,
                    'issue' => $issue,
                ], [
                    'id' => 'ASC',
                ]);

                foreach ($files as $index => $row) {

                    $file = new File($events[$index], $row[0], $row[1], $row[2]);

                    $manager->persist($file);
                    $manager->flush();

                    if ($row[3]) {
                        /** @var Event $event */
                        $event = $manager->getRepository(Event::class)->findOneBy([
                            'type'      => EventType::FILE_DELETED,
                            'issue'     => $issue,
                            'parameter' => $index,
                        ]);

                        $this->setProperty($event, 'parameter', $file->id);
                        $this->setProperty($file, 'removedAt', $event->createdAt);

                        $manager->persist($event);
                    }

                    $this->setProperty($events[$index], 'parameter', $file->id);
                    $manager->persist($events[$index]);
                    $manager->flush();
                }
            }
        }
    }
}
