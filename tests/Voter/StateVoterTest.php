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

use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @coversDefaultClass \eTraxis\Voter\StateVoter
 */
class StateVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private StateRepositoryInterface      $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(State::class);
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnsupportedAttribute()
    {
        [/* skipping */, $state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted('UNKNOWN', $state));
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

        $voter = new StateVoter($manager);
        $this->setProperty($voter, 'attributes', ['UNKNOWN' => null]);

        $this->loginAs('admin@example.com');
        static::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($tokenStorage->getToken(), null, ['UNKNOWN']));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new StateVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        [/* skipping */, $state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        static::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $template, [StateVoter::CREATE_STATE]));
        static::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::UPDATE_STATE]));
        static::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::DELETE_STATE]));
        static::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::SET_INITIAL]));
        static::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::GET_TRANSITIONS]));
        static::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::SET_TRANSITIONS]));
        static::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::GET_RESPONSIBLE_GROUPS]));
        static::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::SET_RESPONSIBLE_GROUPS]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate()
    {
        /** @var \eTraxis\Repository\Contracts\TemplateRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [/* skipping */, $templateB, $templateC] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(StateVoter::CREATE_STATE, $templateB));
        static::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateB));
        static::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateC));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(StateVoter::UPDATE_STATE, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateC));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        [/* skipping */, $stateB, $stateC, $stateD] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateC));
        static::assertTrue($this->security->isGranted(StateVoter::DELETE_STATE, $stateD));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateC));
        static::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateD));
    }

    /**
     * @covers ::isSetInitialGranted
     * @covers ::voteOnAttribute
     */
    public function testSetInitial()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(StateVoter::SET_INITIAL, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL, $stateC));
    }

    /**
     * @covers ::isGetTransitionsGranted
     * @covers ::voteOnAttribute
     */
    public function testGetTransitions()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(StateVoter::GET_TRANSITIONS, $stateB));
        static::assertTrue($this->security->isGranted(StateVoter::GET_TRANSITIONS, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::GET_TRANSITIONS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::GET_TRANSITIONS, $stateC));

        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::GET_TRANSITIONS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::GET_TRANSITIONS, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::GET_TRANSITIONS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::GET_TRANSITIONS, $stateC));
    }

    /**
     * @covers ::isSetTransitionsGranted
     * @covers ::voteOnAttribute
     */
    public function testSetTransitions()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(StateVoter::SET_TRANSITIONS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_TRANSITIONS, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::SET_TRANSITIONS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_TRANSITIONS, $stateC));

        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::SET_TRANSITIONS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_TRANSITIONS, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::SET_TRANSITIONS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_TRANSITIONS, $stateC));
    }

    /**
     * @covers ::isGetResponsibleGroupsGranted
     * @covers ::voteOnAttribute
     */
    public function testGetResponsibleGroups()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateB));
        static::assertTrue($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateC));

        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateC));
    }

    /**
     * @covers ::isSetResponsibleGroupsGranted
     * @covers ::voteOnAttribute
     */
    public function testSetResponsibleGroups()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateC));

        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateB));
        static::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateC));
    }
}
