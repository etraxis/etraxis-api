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

namespace eTraxis\Voter;

use eTraxis\Entity\User;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * @coversDefaultClass \eTraxis\Voter\UserVoter
 */
class UserVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     */
    private $security;

    /**
     * @var \eTraxis\Repository\Contracts\UserRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnsupportedAttribute()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $nhills));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnexpectedAttribute()
    {
        /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage */
        $tokenStorage = self::$container->get('security.token_storage');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new UserVoter($manager);
        $this->setProperty($voter, 'attributes', ['UNKNOWN' => null]);

        $this->loginAs('admin@example.com');
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($tokenStorage->getToken(), null, ['UNKNOWN']));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new UserVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        $nhills = $this->repository->findOneByUsername('nhills@example.com');

        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, null, [UserVoter::CREATE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::UPDATE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::DELETE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::DISABLE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::ENABLE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::UNLOCK_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::SET_PASSWORD]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::MANAGE_MEMBERSHIP]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate()
    {
        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::CREATE_USER));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::CREATE_USER));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::UPDATE_USER, $nhills));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::UPDATE_USER, $nhills));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        $amarvin = $this->repository->findOneByUsername('amarvin@example.com');
        $nhills  = $this->repository->findOneByUsername('nhills@example.com');
        $admin   = $this->repository->findOneByUsername('admin@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::DELETE_USER, $amarvin));
        self::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $admin));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $amarvin));
    }

    /**
     * @covers ::isDisableGranted
     * @covers ::voteOnAttribute
     */
    public function testDisable()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');
        $tberge = $this->repository->findOneByUsername('tberge@example.com');
        $admin  = $this->repository->findOneByUsername('admin@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::DISABLE_USER, $nhills));
        self::assertTrue($this->security->isGranted(UserVoter::DISABLE_USER, $tberge));
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $admin));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $tberge));
    }

    /**
     * @covers ::isEnableGranted
     * @covers ::voteOnAttribute
     */
    public function testEnable()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');
        $tberge = $this->repository->findOneByUsername('tberge@example.com');
        $admin  = $this->repository->findOneByUsername('admin@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::ENABLE_USER, $nhills));
        self::assertTrue($this->security->isGranted(UserVoter::ENABLE_USER, $tberge));
        self::assertTrue($this->security->isGranted(UserVoter::ENABLE_USER, $admin));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $tberge));
    }

    /**
     * @covers ::isUnlockGranted
     * @covers ::voteOnAttribute
     */
    public function testUnlock()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');
        $zapp   = $this->repository->findOneByUsername('jgutmann@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::UNLOCK_USER, $nhills));
        self::assertTrue($this->security->isGranted(UserVoter::UNLOCK_USER, $zapp));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::UNLOCK_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::UNLOCK_USER, $zapp));
    }

    /**
     * @covers ::isSetPasswordGranted
     * @covers ::voteOnAttribute
     */
    public function testSetPassword()
    {
        $nhills   = $this->repository->findOneByUsername('nhills@example.com');
        $einstein = $this->repository->findOneByUsername('einstein@ldap.forumsys.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $einstein));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));

        $this->loginAs('nhills@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));

        $this->loginAs('einstein@ldap.forumsys.com');
        self::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $einstein));
    }

    /**
     * @covers ::isManageMembershipGranted
     * @covers ::voteOnAttribute
     */
    public function testManageMembership()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::MANAGE_MEMBERSHIP, $nhills));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::MANAGE_MEMBERSHIP, $nhills));
    }
}
