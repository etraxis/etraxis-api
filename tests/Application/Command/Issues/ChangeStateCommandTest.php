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
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldValue;
use eTraxis\Entity\Issue;
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * @covers \eTraxis\Application\Command\Issues\Handler\AbstractIssueHandler::validateState
 * @covers \eTraxis\Application\Command\Issues\Handler\ChangeStateHandler::__invoke
 */
class ChangeStateCommandTest extends TransactionalTestCase
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

    public function testSuccessInitialToIntermediate()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        static::assertNotNull($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        static::assertNotSame($assignee, $issue->responsible);
        static::assertGreaterThan(2, time() - $issue->changedAt);
        static::assertCount(3, $values);
        static::assertSame('Description', $values[0]->field->name);
        static::assertSame('New feature', $values[1]->field->name);
        static::assertSame('Priority', $values[2]->field->name);

        $events = count($issue->events);

        $date_value = date('Y-m-d');

        $command = new ChangeStateCommand([
            'issue'       => $issue->id,
            'state'       => $state->id,
            'responsible' => $assignee->id,
            'fields'      => [
                $field->id => $date_value,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        static::assertSame($assignee, $issue->responsible);
        static::assertLessThanOrEqual(2, time() - $issue->changedAt);
        static::assertCount(4, $values);
        static::assertSame('Description', $values[0]->field->name);
        static::assertSame('Due date', $values[1]->field->name);
        static::assertSame('New feature', $values[2]->field->name);
        static::assertSame('Priority', $values[3]->field->name);

        $date = date_create();
        $date->setTimezone(timezone_open($user->timezone));

        static::assertSame($date_value, $date->setTimestamp($values[1]->value)->format('Y-m-d'));

        static::assertCount($events + 2, $issue->events);

        $events = $issue->events;
        $event2 = end($events);
        $event1 = prev($events);

        static::assertSame(EventType::STATE_CHANGED, $event1->type);
        static::assertSame($issue, $event1->issue);
        static::assertSame($user, $event1->user);
        static::assertLessThanOrEqual(2, time() - $event1->createdAt);
        static::assertSame($state->id, $event1->parameter);

        static::assertSame(EventType::ISSUE_ASSIGNED, $event2->type);
        static::assertSame($issue, $event2->issue);
        static::assertSame($user, $event2->user);
        static::assertLessThanOrEqual(2, time() - $event2->createdAt);
        static::assertSame($assignee->id, $event2->parameter);
    }

    public function testSuccessIntermediateToFinal()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        /** @var Issue $duplicate */
        [/* skipping */, /* skipping */, $duplicate] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        static::assertNotNull($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        static::assertNotNull($issue->responsible);
        static::assertGreaterThan(2, time() - $issue->changedAt);
        static::assertCount(4, $values);
        static::assertSame('Description', $values[0]->field->name);
        static::assertSame('Due date', $values[1]->field->name);
        static::assertSame('New feature', $values[2]->field->name);
        static::assertSame('Priority', $values[3]->field->name);

        $events = count($issue->events);

        $command = new ChangeStateCommand([
            'issue'  => $issue->id,
            'state'  => $state->id,
            'fields' => [
                $field->id => $duplicate->id,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        static::assertNull($issue->responsible);
        static::assertLessThanOrEqual(2, time() - $issue->changedAt);
        static::assertCount(5, $values);
        static::assertSame('Description', $values[0]->field->name);
        static::assertSame('Due date', $values[1]->field->name);
        static::assertSame('Issue ID', $values[2]->field->name);
        static::assertSame('New feature', $values[3]->field->name);
        static::assertSame('Priority', $values[4]->field->name);

        static::assertSame($duplicate->id, $values[2]->value);

        static::assertCount($events + 1, $issue->events);

        $events = $issue->events;
        $event  = end($events);

        static::assertSame(EventType::ISSUE_CLOSED, $event->type);
        static::assertSame($issue, $event->issue);
        static::assertSame($user, $event->user);
        static::assertLessThanOrEqual(2, time() - $event->createdAt);
        static::assertSame($state->id, $event->parameter);
    }

    public function testSuccessFinalToInitial()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $state, 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $state, 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $state, 'name' => 'New feature']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        static::assertNotNull($issue);
        static::assertNotNull($issue->closedAt);
        static::assertCount(8, $issue->values);

        $events = count($issue->events);

        $command = new ChangeStateCommand([
            'issue'  => $issue->id,
            'state'  => $state->id,
            'fields' => [
                $field1->id => 2,
                $field2->id => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->id => true,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        static::assertNull($issue->responsible);
        static::assertLessThanOrEqual(2, time() - $issue->changedAt);
        static::assertNull($issue->closedAt);
        static::assertCount(8, $issue->values);
        static::assertCount($events + 1, $issue->events);

        $events = $issue->events;
        $event  = end($events);

        static::assertSame(EventType::ISSUE_REOPENED, $event->type);
        static::assertSame($issue, $event->issue);
        static::assertSame($user, $event->user);
        static::assertLessThanOrEqual(2, time() - $event->createdAt);
        static::assertSame($state->id, $event->parameter);
    }

    public function testSuccessOnlyResponsible()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        static::assertNotNull($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        static::assertNotSame($assignee, $issue->responsible);
        static::assertGreaterThan(2, time() - $issue->changedAt);
        static::assertCount(3, $values);
        static::assertSame('Description', $values[0]->field->name);
        static::assertSame('New feature', $values[1]->field->name);
        static::assertSame('Priority', $values[2]->field->name);

        $command = new ChangeStateCommand([
            'issue'       => $issue->id,
            'state'       => $state->id,
            'responsible' => $assignee->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        static::assertSame($assignee, $issue->responsible);
        static::assertLessThanOrEqual(2, time() - $issue->changedAt);
        static::assertCount(4, $values);
        static::assertSame('Description', $values[0]->field->name);
        static::assertSame('Due date', $values[1]->field->name);
        static::assertSame('New feature', $values[2]->field->name);
        static::assertSame('Priority', $values[3]->field->name);

        $date = date_create();
        $date->setTimezone(timezone_open($user->timezone));

        static::assertNull($values[1]->value);
    }

    public function testSuccessOnlyRequiredFields()
    {
        $this->loginAs('tmarquardt@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Field $field1 */
        [/* skipping */, /* skipping */, $field1] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        /** @var Field $field2 */
        [/* skipping */, /* skipping */, $field2] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        static::assertNotNull($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        static::assertGreaterThan(2, time() - $issue->changedAt);
        static::assertCount(4, $values);
        static::assertSame('Description', $values[0]->field->name);
        static::assertSame('Due date', $values[1]->field->name);
        static::assertSame('New feature', $values[2]->field->name);
        static::assertSame('Priority', $values[3]->field->name);

        $command = new ChangeStateCommand([
            'issue'  => $issue->id,
            'state'  => $state->id,
            'fields' => [
                $field1->id => 216,
                $field2->id => '1:25',
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        static::assertLessThanOrEqual(2, time() - $issue->changedAt);
        static::assertCount(8, $values);
        static::assertSame('Commit ID', $values[0]->field->name);
        static::assertSame('Delta', $values[1]->field->name);
        static::assertSame('Description', $values[2]->field->name);
        static::assertSame('Due date', $values[3]->field->name);
        static::assertSame('Effort', $values[4]->field->name);
        static::assertSame('New feature', $values[5]->field->name);
        static::assertSame('Priority', $values[6]->field->name);
        static::assertSame('Test coverage', $values[7]->field->name);

        static::assertNull($values[0]->value);
        static::assertSame(216, $values[1]->value);
        static::assertSame(85, $values[4]->value);
        static::assertNull($values[7]->value);
    }

    public function testValidationRequiredFields()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\ChangeStateCommand" failed validation.');

        $this->loginAs('tmarquardt@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Field $field1 */
        [/* skipping */, /* skipping */, $field1] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        /** @var Field $field2 */
        [/* skipping */, /* skipping */, $field2] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'  => $issue->id,
            'state'  => $state->id,
            'fields' => [
                $field1->id => null,
                $field2->id => null,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationOnIssueField()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\ChangeStateCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'  => $issue->id,
            'state'  => $state->id,
            'fields' => [
                $field->id => 0,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'       => self::UNKNOWN_ENTITY_ID,
            'state'       => $state->id,
            'responsible' => $assignee->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownState()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue' => $issue->id,
            'state' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginAs('ldoyle@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'       => $issue->id,
            'state'       => $state->id,
            'responsible' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }

    public function testResponsibleDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('The issue cannot be assigned to specified user.');

        $this->loginAs('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'lucas.oconnell@example.com']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'       => $issue->id,
            'state'       => $state->id,
            'responsible' => $assignee->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDeniedByUser()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to change the current state.');

        $this->loginAs('labshire@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'       => $issue->id,
            'state'       => $state->id,
            'responsible' => $assignee->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDeniedByState()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to change the current state to specified one.');

        $this->loginAs('ldoyle@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        /** @var Issue $duplicate */
        [/* skipping */, /* skipping */, $duplicate] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'  => $issue->id,
            'state'  => $state->id,
            'fields' => [
                $field->id => $duplicate->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'       => $issue->id,
            'state'       => $state->id,
            'responsible' => $assignee->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'       => $issue->id,
            'state'       => $state->id,
            'responsible' => $assignee->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $command = new ChangeStateCommand([
            'issue'       => $issue->id,
            'state'       => $state->id,
            'responsible' => $assignee->id,
        ]);

        $this->commandBus->handle($command);
    }
}
