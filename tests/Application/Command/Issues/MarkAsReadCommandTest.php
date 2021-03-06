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

namespace eTraxis\Application\Command\Issues;

use eTraxis\Entity\Issue;
use eTraxis\Entity\LastRead;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\Application\Command\Issues\Handler\MarkAsReadHandler::__invoke
 */
class MarkAsReadCommandTest extends TransactionalTestCase
{
    private IssueRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess()
    {
        $this->loginAs('tmarquardt@example.com');

        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        /** @var LastRead $lastRead */
        $lastRead = $this->doctrine->getRepository(LastRead::class)->findOneBy([
            'issue' => $read,
            'user'  => $user,
        ]);

        static::assertGreaterThan(2, time() - $lastRead->readAt);

        $count = count($this->doctrine->getRepository(LastRead::class)->findAll());

        $command = new MarkAsReadCommand([
            'issues' => [
                $read->id,
                $unread->id,
                $forbidden->id,
                self::UNKNOWN_ENTITY_ID,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($lastRead);

        static::assertCount($count + 1, $this->doctrine->getRepository(LastRead::class)->findAll());
        static::assertLessThanOrEqual(2, time() - $lastRead->readAt);
    }
}
