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

use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @coversDefaultClass \eTraxis\Voter\FieldVoter
 */
class FieldVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private FieldRepositoryInterface      $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
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
        static::assertFalse($this->security->isGranted('UNKNOWN', $field));
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
        static::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($tokenStorage->getToken(), null, ['UNKNOWN']));
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

        static::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $state, [FieldVoter::CREATE_FIELD]));
        static::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::UPDATE_FIELD]));
        static::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::REMOVE_FIELD]));
        static::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::DELETE_FIELD]));
        static::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::GET_PERMISSIONS]));
        static::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::SET_PERMISSIONS]));
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
        static::assertTrue($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateB));
        static::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateB));
        static::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateC));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        [/* skipping */, $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldB));
        static::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldB));
        static::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldC));
    }

    /**
     * @covers ::isRemoveGranted
     * @covers ::voteOnAttribute
     */
    public function testRemove()
    {
        [/* skipping */, $fieldB, $fieldC, $fieldD] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldB));
        static::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldC));
        static::assertTrue($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldD));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldB));
        static::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldC));
        static::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldD));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        [/* skipping */, $fieldB, $fieldC, $fieldD] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldB));
        static::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldC));
        static::assertTrue($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldD));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldB));
        static::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldC));
        static::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldD));
    }

    /**
     * @covers ::isGetPermissionsGranted
     * @covers ::voteOnAttribute
     */
    public function testGetPermissions()
    {
        [/* skipping */, $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(FieldVoter::GET_PERMISSIONS, $fieldB));
        static::assertTrue($this->security->isGranted(FieldVoter::GET_PERMISSIONS, $fieldC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(FieldVoter::GET_PERMISSIONS, $fieldB));
        static::assertFalse($this->security->isGranted(FieldVoter::GET_PERMISSIONS, $fieldC));
    }

    /**
     * @covers ::isSetPermissionsGranted
     * @covers ::voteOnAttribute
     */
    public function testSetPermissions()
    {
        [/* skipping */, $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(FieldVoter::SET_PERMISSIONS, $fieldB));
        static::assertFalse($this->security->isGranted(FieldVoter::SET_PERMISSIONS, $fieldC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(FieldVoter::SET_PERMISSIONS, $fieldB));
        static::assertFalse($this->security->isGranted(FieldVoter::SET_PERMISSIONS, $fieldC));
    }
}
