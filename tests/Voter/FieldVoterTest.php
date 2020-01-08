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

use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * @coversDefaultClass \eTraxis\Voter\FieldVoter
 */
class FieldVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     */
    private $security;

    /**
     * @var \eTraxis\Repository\Contracts\FieldRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnsupportedAttribute()
    {
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $field));
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

        $voter = new FieldVoter($manager);
        $this->setProperty($voter, 'attributes', ['UNKNOWN' => null]);

        $this->loginAs('admin@example.com');
        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($tokenStorage->getToken(), null, ['UNKNOWN']));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new FieldVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $state, [FieldVoter::CREATE_FIELD]));
        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::UPDATE_FIELD]));
        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::REMOVE_FIELD]));
        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::DELETE_FIELD]));
        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::MANAGE_PERMISSIONS]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate()
    {
        /** @var \eTraxis\Repository\Contracts\StateRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(State::class);

        [/* skipping */, $stateB, $stateC] = $repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateB));
        self::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateB));
        self::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateC));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        [/* skipping */, $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldC));
    }

    /**
     * @covers ::isRemoveGranted
     * @covers ::voteOnAttribute
     */
    public function testRemove()
    {
        [/* skipping */, $fieldB, $fieldC, $fieldD] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldC));
        self::assertTrue($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldD));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldC));
        self::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldD));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        [/* skipping */, $fieldB, $fieldC, $fieldD] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldC));
        self::assertTrue($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldD));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldC));
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldD));
    }

    /**
     * @covers ::isManagePermissionsGranted
     * @covers ::voteOnAttribute
     */
    public function testManagePermissions()
    {
        [/* skipping */, $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $fieldC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $fieldC));
    }
}
