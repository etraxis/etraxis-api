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

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Seconds;
use eTraxis\Entity\Issue;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @covers \eTraxis\Application\Command\Issues\Handler\SuspendIssueHandler::__invoke
 */
class SuspendIssueCommandTest extends TransactionalTestCase
{
    private IssueRepositoryInterface $repository;
    private \DateTime                $date;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);

        $this->date = date_create();
        $this->date->setTimezone(timezone_open('UTC'));
        $this->date->setTimestamp(time() + Seconds::ONE_DAY);
        $this->date->setTime(0, 0);
    }

    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        static::assertFalse($issue->isSuspended);

        $events = count($issue->events);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        static::assertTrue($issue->isSuspended);
        static::assertCount($events + 1, $issue->events);

        $event = $issue->events[$events];

        static::assertSame(EventType::ISSUE_SUSPENDED, $event->type);
        static::assertSame($issue, $event->issue);
        static::assertLessThanOrEqual(2, time() - $event->createdAt);
    }

    public function testValidationRequiredFields()
    {
        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\SuspendIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationInvalidDate()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Date must be in future.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => gmdate('Y-m-d'),
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('ldoyle@example.com');

        $command = new SuspendIssueCommand([
            'issue' => self::UNKNOWN_ENTITY_ID,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to suspend this issue.');

        $this->loginAs('nhills@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandBus->handle($command);
    }
}
