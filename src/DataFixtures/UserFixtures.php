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
use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Test fixtures for 'User' entity.
 */
class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private $encoder;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

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

            'einstein@ldap.forumsys.com' => [
                'provider' => AccountProvider::LDAP,
                'uid'      => 'ldap-9fc3012e',
                'fullname' => 'Albert Einstein',
            ],
        ];

        $password = $this->encoder->encodePassword(new User(), 'secret');

        foreach ($data as $email => $row) {

            $user = new User();

            $user->email       = $email;
            $user->password    = $password;
            $user->fullname    = $row['fullname'];
            $user->description = $row['description'] ?? null;

            if ($row['provider'] ?? false) {
                $user->account->provider = $row['provider'];
                $user->account->uid      = $row['uid'];
                $user->password          = null;
            }

            $this->addReference('user:' . $email, $user);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
