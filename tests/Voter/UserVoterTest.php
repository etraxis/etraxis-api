<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Voter;

use eTraxis\Entity\User;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @coversDefaultClass \eTraxis\Voter\UserVoter
 */
class UserVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private UserRepositoryInterface       $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
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
        $nhills = $this->repository->loadUserByUsername('nhills@example.com');

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted('UNKNOWN', $nhills));
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
        static::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($tokenStorage->getToken(), null, ['UNKNOWN']));
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

        $nhills = $this->repository->loadUserByUsername('nhills@example.com');

        static::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, null, [UserVoter::CREATE_USER]));
        static::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::UPDATE_USER]));
        static::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::DELETE_USER]));
        static::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::DISABLE_USER]));
        static::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::ENABLE_USER]));
        static::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::UNLOCK_USER]));
        static::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::SET_PASSWORD]));
        static::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::MANAGE_MEMBERSHIP]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate()
    {
        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(UserVoter::CREATE_USER));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::CREATE_USER));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        $nhills = $this->repository->loadUserByUsername('nhills@example.com');

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(UserVoter::UPDATE_USER, $nhills));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::UPDATE_USER, $nhills));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        $amarvin = $this->repository->loadUserByUsername('amarvin@example.com');
        $nhills  = $this->repository->loadUserByUsername('nhills@example.com');
        $admin   = $this->repository->loadUserByUsername('admin@example.com');

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(UserVoter::DELETE_USER, $amarvin));
        static::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $nhills));
        static::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $admin));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $amarvin));
    }

    /**
     * @covers ::isDisableGranted
     * @covers ::voteOnAttribute
     */
    public function testDisable()
    {
        $nhills = $this->repository->loadUserByUsername('nhills@example.com');
        $tberge = $this->repository->loadUserByUsername('tberge@example.com');
        $admin  = $this->repository->loadUserByUsername('admin@example.com');

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(UserVoter::DISABLE_USER, $nhills));
        static::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $tberge));
        static::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $admin));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $nhills));
        static::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $tberge));
    }

    /**
     * @covers ::isEnableGranted
     * @covers ::voteOnAttribute
     */
    public function testEnable()
    {
        $nhills = $this->repository->loadUserByUsername('nhills@example.com');
        $tberge = $this->repository->loadUserByUsername('tberge@example.com');
        $admin  = $this->repository->loadUserByUsername('admin@example.com');

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $nhills));
        static::assertTrue($this->security->isGranted(UserVoter::ENABLE_USER, $tberge));
        static::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $admin));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $nhills));
        static::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $tberge));
    }

    /**
     * @covers ::isUnlockGranted
     * @covers ::voteOnAttribute
     */
    public function testUnlock()
    {
        $nhills   = $this->repository->loadUserByUsername('nhills@example.com');
        $jgutmann = $this->repository->loadUserByUsername('jgutmann@example.com');

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::UNLOCK_USER, $nhills));
        static::assertTrue($this->security->isGranted(UserVoter::UNLOCK_USER, $jgutmann));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::UNLOCK_USER, $nhills));
        static::assertFalse($this->security->isGranted(UserVoter::UNLOCK_USER, $jgutmann));
    }

    /**
     * @covers ::isSetPasswordGranted
     * @covers ::voteOnAttribute
     */
    public function testSetPassword()
    {
        $nhills   = $this->repository->loadUserByUsername('nhills@example.com');
        $einstein = $this->repository->loadUserByUsername('einstein@ldap.forumsys.com');

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));
        static::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $einstein));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));

        $this->loginAs('nhills@example.com');
        static::assertTrue($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));

        $this->loginAs('einstein@ldap.forumsys.com');
        static::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $einstein));
    }

    /**
     * @covers ::isManageMembershipGranted
     * @covers ::voteOnAttribute
     */
    public function testManageMembership()
    {
        $nhills = $this->repository->loadUserByUsername('nhills@example.com');

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(UserVoter::MANAGE_MEMBERSHIP, $nhills));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(UserVoter::MANAGE_MEMBERSHIP, $nhills));
    }
}
