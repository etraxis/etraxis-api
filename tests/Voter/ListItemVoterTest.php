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
use eTraxis\Entity\ListItem;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * @coversDefaultClass \eTraxis\Voter\ListItemVoter
 */
class ListItemVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     */
    private $security;

    /**
     * @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(ListItem::class);
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnsupportedAttribute()
    {
        [/* skipping */, $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $item));
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

        $voter = new ListItemVoter($manager);
        $this->setProperty($voter, 'attributes', ['UNKNOWN' => null]);

        $this->loginAs('admin@example.com');
        self::assertSame(ListItemVoter::ACCESS_DENIED, $voter->vote($tokenStorage->getToken(), null, ['UNKNOWN']));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new ListItemVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        [/* skipping */, $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        self::assertSame(ListItemVoter::ACCESS_DENIED, $voter->vote($token, $field, [ListItemVoter::CREATE_ITEM]));
        self::assertSame(ListItemVoter::ACCESS_DENIED, $voter->vote($token, $item, [ListItemVoter::UPDATE_ITEM]));
        self::assertSame(ListItemVoter::ACCESS_DENIED, $voter->vote($token, $item, [ListItemVoter::DELETE_ITEM]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate()
    {
        /** @var \eTraxis\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(Field::class);

        [/* skipping */, $fieldB, $fieldC] = $repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        [/* skipping */, $fieldW] = $repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldB));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldC));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldW));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldB));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldC));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldW));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        [/* skipping */, $itemB, $itemC] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(ListItemVoter::UPDATE_ITEM, $itemB));
        self::assertFalse($this->security->isGranted(ListItemVoter::UPDATE_ITEM, $itemC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::UPDATE_ITEM, $itemB));
        self::assertFalse($this->security->isGranted(ListItemVoter::UPDATE_ITEM, $itemC));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        [/* skipping */, $highB, $highC] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);
        [/* skipping */, $lowB, $lowC]   = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $highB));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $highC));
        self::assertTrue($this->security->isGranted(ListItemVoter::DELETE_ITEM, $lowB));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $lowC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $highB));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $highC));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $lowB));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $lowC));
    }
}
