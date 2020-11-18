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

use eTraxis\Entity\Issue;
use eTraxis\Entity\LastRead;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\LastReadRepository
 */
class LastReadRepositoryTest extends TransactionalTestCase
{
    private Contracts\LastReadRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(LastRead::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(LastReadRepository::class, $this->repository);
    }

    /**
     * @covers ::warmup
     */
    public function testWarmup()
    {
        $this->loginAs('fdooley@example.com');

        /** @var Issue $readIssue */
        [$readIssue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unreadIssue */
        [$unreadIssue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        self::assertSame(2, $this->repository->warmup([
            $readIssue->id,
            $unreadIssue->id,
        ]));
    }

    /**
     * @covers ::findLastRead
     */
    public function testFindLastRead()
    {
        $this->loginAs('fdooley@example.com');

        /** @var Issue $readIssue */
        [$readIssue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unreadIssue */
        [$unreadIssue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        self::assertInstanceOf(LastRead::class, $this->repository->findLastRead($readIssue));
        self::assertNull($this->repository->findLastRead($unreadIssue));
    }

    /**
     * @covers ::markAsRead
     */
    public function testMarkAsReadExisting()
    {
        $this->loginAs('fdooley@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var LastRead $read */
        $read = $this->repository->findOneBy([
            'issue' => $issue,
            'user'  => $user,
        ]);

        self::assertNotNull($read);
        self::assertGreaterThan(2, time() - $read->readAt);

        $this->repository->markAsRead($issue);

        /** @var LastRead $read */
        $read = $this->repository->findOneBy([
            'issue' => $issue,
            'user'  => $user,
        ]);

        self::assertNotNull($read);
        self::assertLessThanOrEqual(2, time() - $read->readAt);
    }

    /**
     * @covers ::markAsRead
     */
    public function testMarkAsReadNew()
    {
        $this->loginAs('fdooley@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var LastRead $read */
        $read = $this->repository->findOneBy([
            'issue' => $issue,
            'user'  => $user,
        ]);

        self::assertNull($read);

        $this->repository->markAsRead($issue);

        /** @var LastRead $read */
        $read = $this->repository->findOneBy([
            'issue' => $issue,
            'user'  => $user,
        ]);

        self::assertNotNull($read);
        self::assertLessThanOrEqual(2, time() - $read->readAt);
    }
}
