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
use eTraxis\Entity\Change;
use eTraxis\Entity\DecimalValue;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldValue;
use eTraxis\Entity\Issue;
use eTraxis\Entity\ListItem;
use eTraxis\Entity\StringValue;
use eTraxis\Entity\TextValue;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * @covers \eTraxis\Application\Command\Issues\Handler\UpdateIssueHandler::__invoke
 */
class UpdateIssueCommandTest extends TransactionalTestCase
{
    /**
     * @var \eTraxis\Repository\Contracts\IssueRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \eTraxis\Repository\Contracts\DecimalValueRepositoryInterface $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \eTraxis\Repository\Contracts\StringValueRepositoryInterface $stringRepository */
        $stringRepository = $this->doctrine->getRepository(StringValue::class);

        /** @var \eTraxis\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertGreaterThan(2, time() - $issue->changedAt);
        self::assertSame('Development task 1', $issue->subject);
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5173, $values[$index['Delta']]->value);
        self::assertSame(1440, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        $events  = count($issue->events);
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $values[$index['Priority']]->field->id      => 1,
                $values[$index['Description']]->field->id   => 'Est dolorum omnis accusantium hic veritatis ut.',
                $values[$index['Error']]->field->id         => true,
                $values[$index['Due date']]->field->id      => '2017-04-22',
                $values[$index['Commit ID']]->field->id     => 'fb6c40d246aeeb8934884febcd18d19555fd7725',
                $values[$index['Delta']]->field->id         => 5182,
                $values[$index['Effort']]->field->id        => '7:40',
                $values[$index['Test coverage']]->field->id => '98.52',
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        $date = date_create();
        $date->setTimezone(timezone_open($user->timezone));

        self::assertLessThanOrEqual(2, time() - $issue->changedAt);
        self::assertSame('Test issue', $issue->subject);
        self::assertSame('high', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Est dolorum omnis accusantium hic veritatis ut.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(1, $values[$index['Error']]->value);
        self::assertSame('2017-04-22', $date->setTimestamp($values[$index['Due date']]->value)->format('Y-m-d'));
        self::assertSame('fb6c40d246aeeb8934884febcd18d19555fd7725', $stringRepository->find($values[$index['Commit ID']]->value)->value);
        self::assertSame(5182, $values[$index['Delta']]->value);
        self::assertSame(460, $values[$index['Effort']]->value);
        self::assertSame('98.52', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        self::assertCount($events + 1, $issue->events);
        self::assertCount($changes + 9, $this->doctrine->getRepository(Change::class)->findAll());

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::ISSUE_EDITED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
        self::assertNull($event->parameter);
    }

    public function testSuccessOnlySubject()
    {
        $this->loginAs('ldoyle@example.com');

        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \eTraxis\Repository\Contracts\DecimalValueRepositoryInterface $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \eTraxis\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertGreaterThan(2, time() - $issue->changedAt);
        self::assertSame('Development task 1', $issue->subject);
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5173, $values[$index['Delta']]->value);
        self::assertSame(1440, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        $events  = count($issue->events);
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertLessThanOrEqual(2, time() - $issue->changedAt);
        self::assertSame('Test issue', $issue->subject);
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5173, $values[$index['Delta']]->value);
        self::assertSame(1440, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        self::assertCount($events + 1, $issue->events);
        self::assertCount($changes + 1, $this->doctrine->getRepository(Change::class)->findAll());

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::ISSUE_EDITED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
        self::assertNull($event->parameter);
    }

    public function testSuccessOnlyRequiredFields()
    {
        $this->loginAs('ldoyle@example.com');

        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \eTraxis\Repository\Contracts\DecimalValueRepositoryInterface $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \eTraxis\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertGreaterThan(2, time() - $issue->changedAt);
        self::assertSame('Development task 1', $issue->subject);
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5173, $values[$index['Delta']]->value);
        self::assertSame(1440, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        $events  = count($issue->events);
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => null,
            'fields'  => [
                $values[$index['Priority']]->field->id => 1,
                $values[$index['Delta']]->field->id    => 5182,
                $values[$index['Effort']]->field->id   => '7:40',
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertLessThanOrEqual(2, time() - $issue->changedAt);
        self::assertSame('Development task 1', $issue->subject);
        self::assertSame('high', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5182, $values[$index['Delta']]->value);
        self::assertSame(460, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        self::assertCount($events + 1, $issue->events);
        self::assertCount($changes + 3, $this->doctrine->getRepository(Change::class)->findAll());

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::ISSUE_EDITED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
        self::assertNull($event->parameter);
    }

    public function testValidationRequiredFields()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\UpdateIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        $command = new UpdateIssueCommand([
            'issue'  => $issue->id,
            'fields' => [
                $values[0]->field->id => null,
                $values[1]->field->id => null,
                $values[2]->field->id => null,
                $values[3]->field->id => null,
                $values[4]->field->id => null,
                $values[5]->field->id => null,
                $values[6]->field->id => null,
                $values[7]->field->id => null,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationOnListField()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\UpdateIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 4,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationOnTextField()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\UpdateIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => str_pad(null, TextValue::MAX_VALUE + 1, '*'),
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationOnCheckboxField()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\UpdateIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Error']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 0,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationOnDateField()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\UpdateIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Due date']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => '2004-07-08',
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationOnStringField()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\UpdateIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Commit ID']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => str_pad(null, 41, '*'),
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationOnNumberField()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\UpdateIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Delta']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => -1,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationOnDurationField()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\UpdateIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Effort']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => '1000000:00',
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testValidationOnDecimalField()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Message of type "eTraxis\Application\Command\Issues\UpdateIssueCommand" failed validation.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Test coverage']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => '100.01',
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('ldoyle@example.com');

        $command = new UpdateIssueCommand([
            'issue'   => self::UNKNOWN_ENTITY_ID,
            'subject' => 'Test issue',
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to edit this issue.');

        $this->loginAs('labshire@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandBus->handle($command);
    }

    public function testFrozenIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $issue->template->frozenTime = 1;

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandBus->handle($command);
    }
}
