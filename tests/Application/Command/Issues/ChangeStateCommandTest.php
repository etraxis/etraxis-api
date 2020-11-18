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
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        self::assertNotSame($assignee, $issue->responsible);
        self::assertGreaterThan(2, time() - $issue->changedAt);
        self::assertCount(3, $values);
        self::assertSame('Description', $values[0]->field->name);
        self::assertSame('New feature', $values[1]->field->name);
        self::assertSame('Priority', $values[2]->field->name);

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

        self::assertSame($assignee, $issue->responsible);
        self::assertLessThanOrEqual(2, time() - $issue->changedAt);
        self::assertCount(4, $values);
        self::assertSame('Description', $values[0]->field->name);
        self::assertSame('Due date', $values[1]->field->name);
        self::assertSame('New feature', $values[2]->field->name);
        self::assertSame('Priority', $values[3]->field->name);

        $date = date_create();
        $date->setTimezone(timezone_open($user->timezone));

        self::assertSame($date_value, $date->setTimestamp($values[1]->value)->format('Y-m-d'));

        self::assertCount($events + 2, $issue->events);

        $events = $issue->events;
        $event2 = end($events);
        $event1 = prev($events);

        self::assertSame(EventType::STATE_CHANGED, $event1->type);
        self::assertSame($issue, $event1->issue);
        self::assertSame($user, $event1->user);
        self::assertLessThanOrEqual(2, time() - $event1->createdAt);
        self::assertSame($state->id, $event1->parameter);

        self::assertSame(EventType::ISSUE_ASSIGNED, $event2->type);
        self::assertSame($issue, $event2->issue);
        self::assertSame($user, $event2->user);
        self::assertLessThanOrEqual(2, time() - $event2->createdAt);
        self::assertSame($assignee->id, $event2->parameter);
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
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        self::assertNotNull($issue->responsible);
        self::assertGreaterThan(2, time() - $issue->changedAt);
        self::assertCount(4, $values);
        self::assertSame('Description', $values[0]->field->name);
        self::assertSame('Due date', $values[1]->field->name);
        self::assertSame('New feature', $values[2]->field->name);
        self::assertSame('Priority', $values[3]->field->name);

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

        self::assertNull($issue->responsible);
        self::assertLessThanOrEqual(2, time() - $issue->changedAt);
        self::assertCount(5, $values);
        self::assertSame('Description', $values[0]->field->name);
        self::assertSame('Due date', $values[1]->field->name);
        self::assertSame('Issue ID', $values[2]->field->name);
        self::assertSame('New feature', $values[3]->field->name);
        self::assertSame('Priority', $values[4]->field->name);

        self::assertSame($duplicate->id, $values[2]->value);

        self::assertCount($events + 1, $issue->events);

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::ISSUE_CLOSED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
        self::assertSame($state->id, $event->parameter);
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
        self::assertNotNull($issue);
        self::assertNotNull($issue->closedAt);
        self::assertCount(8, $issue->values);

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

        self::assertNull($issue->responsible);
        self::assertLessThanOrEqual(2, time() - $issue->changedAt);
        self::assertNull($issue->closedAt);
        self::assertCount(8, $issue->values);
        self::assertCount($events + 1, $issue->events);

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::ISSUE_REOPENED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
        self::assertSame($state->id, $event->parameter);
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
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        self::assertNotSame($assignee, $issue->responsible);
        self::assertGreaterThan(2, time() - $issue->changedAt);
        self::assertCount(3, $values);
        self::assertSame('Description', $values[0]->field->name);
        self::assertSame('New feature', $values[1]->field->name);
        self::assertSame('Priority', $values[2]->field->name);

        $command = new ChangeStateCommand([
            'issue'       => $issue->id,
            'state'       => $state->id,
            'responsible' => $assignee->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        self::assertSame($assignee, $issue->responsible);
        self::assertLessThanOrEqual(2, time() - $issue->changedAt);
        self::assertCount(4, $values);
        self::assertSame('Description', $values[0]->field->name);
        self::assertSame('Due date', $values[1]->field->name);
        self::assertSame('New feature', $values[2]->field->name);
        self::assertSame('Priority', $values[3]->field->name);

        $date = date_create();
        $date->setTimezone(timezone_open($user->timezone));

        self::assertNull($values[1]->value);
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
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->field->name, $value2->field->name));

        self::assertGreaterThan(2, time() - $issue->changedAt);
        self::assertCount(4, $values);
        self::assertSame('Description', $values[0]->field->name);
        self::assertSame('Due date', $values[1]->field->name);
        self::assertSame('New feature', $values[2]->field->name);
        self::assertSame('Priority', $values[3]->field->name);

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

        self::assertLessThanOrEqual(2, time() - $issue->changedAt);
        self::assertCount(8, $values);
        self::assertSame('Commit ID', $values[0]->field->name);
        self::assertSame('Delta', $values[1]->field->name);
        self::assertSame('Description', $values[2]->field->name);
        self::assertSame('Due date', $values[3]->field->name);
        self::assertSame('Effort', $values[4]->field->name);
        self::assertSame('New feature', $values[5]->field->name);
        self::assertSame('Priority', $values[6]->field->name);
        self::assertSame('Test coverage', $values[7]->field->name);

        self::assertNull($values[0]->value);
        self::assertSame(216, $values[1]->value);
        self::assertSame(85, $values[4]->value);
        self::assertNull($values[7]->value);
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
