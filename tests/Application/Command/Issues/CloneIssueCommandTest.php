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

namespace eTraxis\Application\Command\Issues;

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Dictionary\StateResponsible;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldValue;
use eTraxis\Entity\Group;
use eTraxis\Entity\Issue;
use eTraxis\Entity\ListItem;
use eTraxis\Entity\State;
use eTraxis\Entity\StateResponsibleGroup;
use eTraxis\Entity\TextValue;
use eTraxis\Entity\User;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * @covers \eTraxis\Application\Command\Issues\Handler\AbstractIssueHandler::validateState
 * @covers \eTraxis\Application\Command\Issues\Handler\CloneIssueHandler::__invoke
 */
class CloneIssueCommandTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /**
     * @var \eTraxis\Repository\Contracts\IssueRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccessNoResponsible()
    {
        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'New feature']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand([
            'issue'   => $origin->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field1->id => 2,
                $field2->id => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->id => true,
            ],
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertInstanceOf(Issue::class, $issue);
        self::assertSame($result, $issue);

        $this->doctrine->getManager()->refresh($issue);

        self::assertSame('Test issue', $issue->subject);
        self::assertSame($origin->template->initialState, $issue->state);
        self::assertSame('nhills@example.com', $issue->author->email);
        self::assertNull($issue->responsible);
        self::assertLessThanOrEqual(2, time() - $issue->createdAt);
        self::assertLessThanOrEqual(2, $issue->changedAt - $issue->createdAt);
        self::assertNull($issue->closedAt);

        self::assertCount(1, $issue->events);

        $event = $issue->events[0];

        self::assertSame(EventType::ISSUE_CREATED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($issue->author, $event->user);
        self::assertLessThanOrEqual(2, $event->createdAt - $issue->createdAt);
        self::assertSame($issue->state->id, $event->parameter);

        $values = array_filter($issue->values, function (FieldValue $value) use ($origin) {
            return $value->field->state === $origin->template->initialState;
        });

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return $value1->field->position - $value2->field->position;
        });

        self::assertCount(3, $values);

        self::assertSame($field1, $values[0]->field);
        self::assertSame($field2, $values[1]->field);
        self::assertSame($field3, $values[2]->field);

        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);
        $listValue      = $listRepository->findOneByValue($field1, 2);

        /** @var \eTraxis\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);
        $textValue      = $textRepository->get('Est dolorum omnis accusantium hic veritatis ut.');

        self::assertSame($listValue->id, $values[0]->value);
        self::assertSame($textValue->id, $values[1]->value);
        self::assertSame(1, $values[2]->value);
    }

    public function testSuccessWithResponsible()
    {
        $this->loginAs('nhills@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, /* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $responsibleGroup = new StateResponsibleGroup($state, $group);

        $this->doctrine->getManager()->persist($responsibleGroup);
        $this->doctrine->getManager()->flush();

        $this->setProperty($state, 'responsible', StateResponsible::ASSIGN);

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'New feature']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand([
            'issue'       => $origin->id,
            'subject'     => 'Test issue',
            'responsible' => $user->id,
            'fields'      => [
                $field1->id => 2,
                $field2->id => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->id => true,
            ],
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertInstanceOf(Issue::class, $issue);
        self::assertSame($result, $issue);

        $this->doctrine->getManager()->refresh($issue);

        self::assertSame('Test issue', $issue->subject);
        self::assertSame($origin->template->initialState, $issue->state);
        self::assertSame('nhills@example.com', $issue->author->email);
        self::assertSame('dquigley@example.com', $issue->responsible->email);
        self::assertLessThanOrEqual(2, time() - $issue->createdAt);
        self::assertLessThanOrEqual(2, $issue->changedAt - $issue->createdAt);
        self::assertNull($issue->closedAt);

        self::assertCount(2, $issue->events);

        $event1 = $issue->events[0];
        $event2 = $issue->events[1];

        self::assertSame(EventType::ISSUE_CREATED, $event1->type);
        self::assertSame($issue, $event1->issue);
        self::assertSame($issue->author, $event1->user);
        self::assertSame($issue->createdAt, $event1->createdAt);
        self::assertSame($issue->state->id, $event1->parameter);

        self::assertSame(EventType::ISSUE_ASSIGNED, $event2->type);
        self::assertSame($issue, $event2->issue);
        self::assertSame($issue->author, $event2->user);
        self::assertLessThanOrEqual(2, $event2->createdAt - $issue->createdAt);
        self::assertSame($issue->responsible->id, $event2->parameter);
    }

    public function testFailedWithResponsible()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\CloneIssueCommand" failed validation.');

        $this->loginAs('nhills@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, /* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $responsibleGroup = new StateResponsibleGroup($state, $group);

        $this->doctrine->getManager()->persist($responsibleGroup);
        $this->doctrine->getManager()->flush();

        $this->setProperty($state, 'responsible', StateResponsible::ASSIGN);

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'New feature']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand([
            'issue'   => $origin->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field1->id => 2,
                $field2->id => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->id => true,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuccessOnlyRequiredFields()
    {
        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand([
            'issue'   => $origin->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field1->id => 2,
            ],
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertInstanceOf(Issue::class, $issue);
        self::assertSame($result, $issue);

        $this->doctrine->getManager()->refresh($issue);

        self::assertSame('Test issue', $issue->subject);
        self::assertSame($origin->template->initialState, $issue->state);
        self::assertSame('nhills@example.com', $issue->author->email);
        self::assertNull($issue->responsible);
        self::assertLessThanOrEqual(2, time() - $issue->createdAt);
        self::assertLessThanOrEqual(2, $issue->changedAt - $issue->createdAt);
        self::assertNull($issue->closedAt);

        self::assertCount(1, $issue->events);

        $event = $issue->events[0];

        self::assertSame(EventType::ISSUE_CREATED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($issue->author, $event->user);
        self::assertLessThanOrEqual(2, $event->createdAt - $issue->createdAt);
        self::assertSame($issue->state->id, $event->parameter);

        $values = array_filter($issue->values, function (FieldValue $value) use ($origin) {
            return $value->field->state === $origin->template->initialState;
        });

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return $value1->field->position - $value2->field->position;
        });

        self::assertCount(3, $values);

        self::assertSame($field1, $values[0]->field);

        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);
        $listValue      = $listRepository->findOneByValue($field1, 2);

        self::assertSame($listValue->id, $values[0]->value);
        self::assertNull($values[1]->value);
        self::assertNull($values[2]->value);
    }

    public function testValidationRequiredFields()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\CloneIssueCommand" failed validation.');

        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new CloneIssueCommand([
            'issue'   => $origin->id,
            'subject' => 'Test issue',
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        $command = new CloneIssueCommand([
            'issue'   => self::UNKNOWN_ENTITY_ID,
            'subject' => 'Test issue',
            'fields'  => [
                $field1->id => 2,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginAs('nhills@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->setProperty($state, 'responsible', StateResponsible::ASSIGN);

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        $command = new CloneIssueCommand([
            'issue'       => $origin->id,
            'subject'     => 'Test issue',
            'responsible' => self::UNKNOWN_ENTITY_ID,
            'fields'      => [
                $field1->id => 2,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new issue.');

        $this->loginAs('labshire@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        $command = new CloneIssueCommand([
            'issue'   => $origin->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field1->id => 2,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testResponsibleDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('The issue cannot be assigned to specified user.');

        $this->loginAs('nhills@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->setProperty($state, 'responsible', StateResponsible::ASSIGN);

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand([
            'issue'       => $origin->id,
            'subject'     => 'Test issue',
            'responsible' => $user->id,
            'fields'      => [
                $field1->id => 2,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [$origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        $command = new CloneIssueCommand([
            'issue'   => $origin->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field1->id => 2,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */, $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->template->initialState, 'name' => 'Priority']);

        $command = new CloneIssueCommand([
            'issue'   => $origin->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field1->id => 2,
            ],
        ]);

        $this->commandBus->handle($command);
    }
}
