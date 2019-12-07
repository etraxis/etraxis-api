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
use Doctrine\Common\Persistence\ObjectManager;
use eTraxis\Entity\User;

/**
 * Fixtures for first-time deployment to production.
 */
class ProductionFixtures extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();

        $user->email       = 'admin@example.com';
        $user->fullname    = 'eTraxis Admin';
        $user->description = 'Built-in administrator';

        $manager->persist($user);
        $manager->flush();
    }
}
