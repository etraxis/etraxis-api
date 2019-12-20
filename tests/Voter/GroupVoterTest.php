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

use eTraxis\Entity\Group;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * @coversDefaultClass \eTraxis\Voter\GroupVoter
 */
class GroupVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     */
    private $security;

    /**
     * @var \eTraxis\Repository\Contracts\GroupRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnsupportedAttribute()
    {
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $group));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnexpectedAttribute()
    {
        /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $token_storage */
        $tokens = self::$container->get('security.token_storage');

        $voter = new GroupVoter();
        $this->setProperty($voter, 'attributes', ['UNKNOWN' => null]);

        $this->loginAs('admin@example.com');
        self::assertSame(GroupVoter::ACCESS_DENIED, $voter->vote($tokens->getToken(), null, ['UNKNOWN']));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous()
    {
        $voter = new GroupVoter();
        $token = new AnonymousToken('', 'anon.');

        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        self::assertSame(GroupVoter::ACCESS_DENIED, $voter->vote($token, null, [GroupVoter::CREATE_GROUP]));
        self::assertSame(GroupVoter::ACCESS_DENIED, $voter->vote($token, $group, [GroupVoter::UPDATE_GROUP]));
        self::assertSame(GroupVoter::ACCESS_DENIED, $voter->vote($token, $group, [GroupVoter::DELETE_GROUP]));
        self::assertSame(GroupVoter::ACCESS_DENIED, $voter->vote($token, $group, [GroupVoter::MANAGE_MEMBERSHIP]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate()
    {
        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::CREATE_GROUP));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::CREATE_GROUP));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::UPDATE_GROUP, $group));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::UPDATE_GROUP, $group));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::DELETE_GROUP, $group));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::DELETE_GROUP, $group));
    }

    /**
     * @covers ::isManageMembershipGranted
     * @covers ::voteOnAttribute
     */
    public function testManageMembership()
    {
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group));
    }
}
