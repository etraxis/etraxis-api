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
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldTypes\NumberInterface;
use eTraxis\Entity\FieldTypes\TextInterface;

/**
 * Test fixtures for 'Field' entity.
 */
class FieldFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            StateFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'new' => [
                [
                    'type'     => FieldType::LIST,
                    'name'     => 'Priority',
                    'required' => true,
                    'position' => 1,
                ],
                [
                    'type'       => FieldType::TEXT,
                    'name'       => 'Description',
                    'required'   => false,
                    'position'   => 2,
                    'parameters' => function (Field $field) use ($manager) {
                        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
                        /** @var TextInterface $facade */
                        $facade = $field->getFacade($manager);
                        $facade
                            ->setMaximumLength(TextInterface::MAX_LENGTH)
                            ->setDefaultValue('How to reproduce:');
                    },
                ],
                [
                    'type'     => FieldType::CHECKBOX,
                    'name'     => 'Error',
                    'required' => false,
                    'position' => 3,
                    'deleted'  => true,
                ],
                [
                    'type'     => FieldType::CHECKBOX,
                    'name'     => 'New feature',
                    'required' => false,
                    'position' => 3,
                ],
            ],

            'assigned' => [
                [
                    'type'       => FieldType::DATE,
                    'name'       => 'Due date',
                    'required'   => false,
                    'position'   => 1,
                    'parameters' => function (Field $field) use ($manager) {
                        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
                        /** @var \eTraxis\Entity\FieldTypes\DateInterface $facade */
                        $facade = $field->getFacade($manager);
                        $facade
                            ->setMinimumValue(0)
                            ->setMaximumValue(14)
                            ->setDefaultValue(14);
                    },
                ],
            ],

            'completed' => [
                [
                    'type'       => FieldType::STRING,
                    'name'       => 'Commit ID',
                    'required'   => false,
                    'position'   => 1,
                    'parameters' => function (Field $field) use ($manager) {
                        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
                        /** @var \eTraxis\Entity\FieldTypes\StringInterface $facade */
                        $facade = $field->getFacade($manager);
                        $facade
                            ->setMaximumLength(40)
                            ->setDefaultValue('Git commit ID');
                    },
                ],
                [
                    'type'        => FieldType::NUMBER,
                    'name'        => 'Delta',
                    'description' => 'NCLOC',
                    'required'    => true,
                    'position'    => 2,
                    'parameters'  => function (Field $field) use ($manager) {
                        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
                        /** @var NumberInterface $facade */
                        $facade = $field->getFacade($manager);
                        $facade
                            ->setMinimumValue(0)
                            ->setMaximumValue(NumberInterface::MAX_VALUE);
                    },
                ],
                [
                    'type'        => FieldType::DURATION,
                    'name'        => 'Effort',
                    'description' => 'HH:MM',
                    'required'    => true,
                    'position'    => 3,
                    'parameters'  => function (Field $field) use ($manager) {
                        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
                        /** @var \eTraxis\Entity\FieldTypes\DurationInterface $facade */
                        $facade = $field->getFacade($manager);
                        $facade
                            ->setMinimumValue('0:00')
                            ->setMaximumValue('999999:59');
                    },
                ],
                [
                    'type'       => FieldType::DECIMAL,
                    'name'       => 'Test coverage',
                    'required'   => false,
                    'position'   => 4,
                    'parameters' => function (Field $field) use ($manager) {
                        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
                        /** @var \eTraxis\Entity\FieldTypes\DecimalInterface $facade */
                        $facade = $field->getFacade($manager);
                        $facade
                            ->setMinimumValue('0')
                            ->setMaximumValue('100');
                    },
                ],
            ],

            'duplicated' => [
                [
                    'type'     => FieldType::ISSUE,
                    'name'     => 'Task ID',
                    'required' => true,
                    'position' => 1,
                    'deleted'  => true,
                ],
                [
                    'type'     => FieldType::ISSUE,
                    'name'     => 'Issue ID',
                    'required' => true,
                    'position' => 1,
                ],
            ],

            'submitted' => [
                [
                    'type'       => FieldType::TEXT,
                    'name'       => 'Details',
                    'required'   => true,
                    'position'   => 1,
                    'parameters' => function (Field $field) use ($manager) {
                        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
                        /** @var TextInterface $facade */
                        $facade = $field->getFacade($manager);
                        $facade
                            ->setMaximumLength(250);
                    },
                ],
            ],

            'opened' => [],

            'resolved' => [],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            foreach ($data as $sref => $fields) {

                /** @var \eTraxis\Entity\State $state */
                $state = $this->getReference(sprintf('%s:%s', $sref, $pref));

                foreach ($fields as $row) {

                    $field = new Field($state, $row['type']);

                    $field->position    = $row['position'];
                    $field->name        = $row['name'];
                    $field->description = $row['description'] ?? null;
                    $field->isRequired  = $row['required'];

                    if ($row['parameters'] ?? false) {
                        $row['parameters']($field);
                    }

                    if ($row['deleted'] ?? false) {
                        $field->remove();
                    }

                    $this->addReference(sprintf('%s:%s:%s', $sref, $pref, mb_strtolower($row['name'])), $field);

                    $manager->persist($field);
                }
            }
        }

        $manager->flush();
    }
}
