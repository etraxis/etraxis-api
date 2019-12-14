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
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\Persistence\ObjectManager;
use eTraxis\Application\Dictionary\Timezone;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Fixtures for first-time deployment to production.
 */
class ProductionFixtures extends Fixture implements FixtureGroupInterface
{
    private $encoder;
    private $locale;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param UserPasswordEncoderInterface $encoder
     * @param string                       $locale
     */
    public function __construct(UserPasswordEncoderInterface $encoder, string $locale)
    {
        $this->encoder = $encoder;
        $this->locale  = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public static function getGroups(): array
    {
        return ['prod'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();

        $user->email       = 'admin@example.com';
        $user->password    = $this->encoder->encodePassword($user, 'secret');
        $user->fullname    = 'eTraxis Admin';
        $user->description = 'Built-in administrator';
        $user->isAdmin     = true;
        $user->locale      = $this->locale;
        $user->timezone    = Timezone::FALLBACK;

        $manager->persist($user);
        $manager->flush();
    }
}
