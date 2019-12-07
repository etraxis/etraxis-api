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
use eTraxis\Entity\User;

/**
 * Test fixtures for 'User' entity.
 */
class UserFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            ProductionFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'artem@example.com' => [
                'fullname' => 'Artem Rodygin',
            ],
        ];

        foreach ($data as $email => $row) {

            $user = new User();

            $user->email       = $email;
            $user->fullname    = $row['fullname'];
            $user->description = $row['description'] ?? null;

            $this->addReference('user:' . $email, $user);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
