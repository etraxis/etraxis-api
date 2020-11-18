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

namespace eTraxis\Repository;

use eTraxis\Entity\Change;
use eTraxis\Entity\Issue;
use eTraxis\Entity\StringValue;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\IssueRepository
 */
class IssueRepositoryTest extends TransactionalTestCase
{
    private Contracts\IssueRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(IssueRepository::class, $this->repository);
    }

    /**
     * @covers ::find
     */
    public function testFind()
    {
        [$expected] = $this->repository->findBy(['subject' => 'Development task 1']);
        self::assertNotNull($expected);

        $value = $this->repository->find($expected->id);
        self::assertSame($expected, $value);
    }

    /**
     * @covers ::getTransitionsByUser
     */
    public function testGetTransitionsByUser()
    {
        /** @var Issue $issue4 */
        [/* skipping */, /* skipping */, $issue4] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $issue6 */
        [/* skipping */, /* skipping */, $issue6] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        /** @var User $manager Manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $support Support engineer */
        $support = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'cbatz@example.com']);

        /** @var User $author4 A client (the author of the issue 4) */
        $author4 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dtillman@example.com']);

        /** @var User $author6 A client (the author of the issue 6) */
        $author6 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'lucas.oconnell@example.com']);

        $states = $this->repository->getTransitionsByUser($issue4, $manager);
        self::assertCount(1, $states);
        self::assertSame('Resolved', $states[0]->name);

        $states = $this->repository->getTransitionsByUser($issue4, $support);
        self::assertCount(1, $states);
        self::assertSame('Resolved', $states[0]->name);

        $states = $this->repository->getTransitionsByUser($issue4, $author4);
        self::assertCount(1, $states);
        self::assertSame('Resolved', $states[0]->name);

        $states = $this->repository->getTransitionsByUser($issue4, $author6);
        self::assertCount(0, $states);

        $states = $this->repository->getTransitionsByUser($issue6, $manager);
        self::assertCount(1, $states);
        self::assertSame('Opened', $states[0]->name);

        $states = $this->repository->getTransitionsByUser($issue6, $support);
        self::assertCount(1, $states);
        self::assertSame('Opened', $states[0]->name);

        // Author should be able to move the issue to a final state,
        // but the issue has unclosed dependencies.
        $states = $this->repository->getTransitionsByUser($issue6, $author6);
        self::assertCount(0, $states);
    }

    /**
     * @covers ::getResponsiblesByUser
     */
    public function testGetResponsiblesByUser()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $users = $this->repository->getResponsiblesByUser($issue, $manager);
        self::assertCount(4, $users);

        $expected = [
            'Carter Batz',
            'Kailyn Bahringer',
            'Tony Buckridge',
            'Tracy Marquardt',
        ];

        $actual = array_map(fn (User $user) => $user->fullname, $users);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getResponsiblesByUser
     */
    public function testGetResponsiblesSkipCurrentByUser()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $users = $this->repository->getResponsiblesByUser($issue, $manager, true);
        self::assertCount(3, $users);

        $expected = [
            'Kailyn Bahringer',
            'Tony Buckridge',
            'Tracy Marquardt',
        ];

        $actual = array_map(fn (User $user) => $user->fullname, $users);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::changeSubject
     */
    public function testChangeSubject()
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $this->repository->changeSubject($issue, $issue->events[0], 'Development task 1');
        $this->doctrine->getManager()->flush();
        self::assertCount($changes, $this->doctrine->getRepository(Change::class)->findAll());

        $this->repository->changeSubject($issue, $issue->events[0], 'Development task X');
        $this->doctrine->getManager()->flush();
        self::assertSame('Development task X', $issue->subject);
        self::assertCount($changes + 1, $this->doctrine->getRepository(Change::class)->findAll());

        /** @var Change $change */
        [$change] = $this->doctrine->getRepository(Change::class)->findBy([], ['id' => 'DESC']);

        /** @var Contracts\StringValueRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(StringValue::class);

        self::assertNull($change->field);
        self::assertSame('Development task 1', $repository->find($change->oldValue)->value);
        self::assertSame('Development task X', $repository->find($change->newValue)->value);
    }
}
