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
use eTraxis\Entity\ListItem;

/**
 * Test fixtures for 'ListItem' entity.
 */
class ListItemFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            FieldFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            1 => 'high',
            2 => 'normal',
            3 => 'low',
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            foreach ($data as $value => $text) {

                /** @var \eTraxis\Entity\Field $field */
                $field = $this->getReference(sprintf('new:%s:priority', $pref));

                $item = new ListItem($field);

                $item->value = $value;
                $item->text  = $text;

                $manager->persist($item);
            }
        }

        $manager->flush();

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            /** @var \eTraxis\Entity\Field $field */
            $field = $this->getReference(sprintf('new:%s:priority', $pref));

            /** @var ListItem $item */
            $item = $manager->getRepository(ListItem::class)->findOneBy([
                'field' => $field,
                'value' => 2,
            ]);

            /** @var \Doctrine\ORM\EntityManagerInterface $manager */
            /** @var \eTraxis\Entity\FieldTypes\ListInterface $facade */
            $facade = $field->getFacade($manager);
            $facade->setDefaultValue($item);

            $manager->persist($field);
        }

        $manager->flush();
    }
}
