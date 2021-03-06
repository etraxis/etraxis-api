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

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Change;
use eTraxis\Entity\DecimalValue;
use eTraxis\Entity\FieldValue;
use eTraxis\Entity\Issue;
use eTraxis\Entity\ListItem;
use eTraxis\Entity\StringValue;
use eTraxis\Entity\TextValue;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\FieldValueRepository
 */
class FieldValueRepositoryTest extends TransactionalTestCase
{
    private Contracts\FieldValueRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(FieldValue::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        static::assertInstanceOf(FieldValueRepository::class, $this->repository);
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetNullFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::STRING);

        $value = reset($values);
        static::assertNull($this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetCheckboxFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::CHECKBOX);

        $value = reset($values);
        static::assertTrue($this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetDateFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::DATE);

        $value = reset($values);
        $date  = date_create();
        $date->setTimestamp($value->value)->setTimezone(timezone_open($user->timezone));
        static::assertSame($date->format('Y-m-d'), $this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetDecimalFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::DECIMAL);

        $value = reset($values);
        static::assertSame('98.49', $this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetDurationFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::DURATION);

        $value = reset($values);
        static::assertSame('1:20', $this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetIssueFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        /** @var Issue $duplicate */
        [$duplicate] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::ISSUE);

        $value = reset($values);
        static::assertSame($duplicate->id, $this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetListFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::LIST);

        $value = reset($values);
        static::assertSame(2, $this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetNumberFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::NUMBER);

        $value = reset($values);
        static::assertSame(5173, $this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetStringFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::STRING);

        $value = reset($values);
        static::assertSame('940059027173b8e8e1e3e874681f012f1f3bcf1d', $this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetTextFieldValue()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::TEXT);

        $value = reset($values);
        static::assertSame('Quas sunt reprehenderit vero accusantium.', $this->repository->getFieldValue($value, $user));
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetCheckboxFieldValue()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::CHECKBOX);

        $value = reset($values);
        static::assertSame(1, $value->value);

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, false);
        static::assertNotNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::CHECKBOX);

        $value = reset($values);
        static::assertSame(0, $value->value);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetDateFieldValue()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::DATE);

        $value = reset($values);
        static::assertSame('2015-04-28', date('Y-m-d', $value->value));

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, '2015-04-23');
        static::assertNotNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::DATE);

        $value = reset($values);
        $date  = date_create();
        $date->setTimestamp($value->value)->setTimezone(timezone_open($issue->events[0]->user->timezone));
        static::assertSame('2015-04-23', $date->format('Y-m-d'));
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetDecimalFieldValue()
    {
        /** @var Contracts\DecimalValueRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::DECIMAL);

        $value = reset($values);
        static::assertSame('98.49', $repository->find($value->value)->value);

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, '3.1415');
        static::assertNotNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::DECIMAL);

        $value = reset($values);
        static::assertSame('3.1415', $repository->find($value->value)->value);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetDurationFieldValue()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::DURATION);

        $value = reset($values);
        static::assertSame(1440, $value->value);

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, '11:52');
        static::assertNotNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::DURATION);

        $value = reset($values);
        static::assertSame(712, $value->value);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetIssueFieldValue()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        /** @var Issue $duplicate1 */
        /** @var Issue $duplicate2 */
        [$duplicate1] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [$duplicate2] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::ISSUE);

        $value = reset($values);
        static::assertSame($duplicate1->id, $value->value);

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, $duplicate2->id);
        static::assertNotNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::ISSUE);

        $value = reset($values);
        static::assertSame($duplicate2->id, $value->value);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetIssueFieldValueFailure()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        /** @var Issue $duplicate1 */
        [$duplicate1] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::ISSUE);

        $value = reset($values);
        static::assertSame($duplicate1->id, $value->value);

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, self::UNKNOWN_ENTITY_ID);
        static::assertNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::ISSUE);

        $value = reset($values);
        static::assertSame($duplicate1->id, $value->value);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetListFieldValue()
    {
        /** @var Contracts\ListItemRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::LIST);

        $value = reset($values);
        static::assertSame('normal', $repository->find($value->value)->text);

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, 3);
        static::assertNotNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::LIST);

        $value = reset($values);
        static::assertSame('low', $repository->find($value->value)->text);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetListFieldValueFailure()
    {
        /** @var Contracts\ListItemRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::LIST);

        $value = reset($values);
        static::assertSame('normal', $repository->find($value->value)->text);

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, 4);
        static::assertNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::LIST);

        $value = reset($values);
        static::assertSame('normal', $repository->find($value->value)->text);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetNumberFieldValue()
    {
        /** @var Issue $issue1 */
        /** @var Issue $issue6 */
        [$issue1] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        [$issue6] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $values1 = array_filter($issue1->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::NUMBER);
        $values6 = array_filter($issue6->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::NUMBER);

        static::assertNotEmpty($values1);
        static::assertEmpty($values6);

        $value = reset($values1);
        static::assertSame(5173, $value->value);

        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $result1 = $this->repository->setFieldValue($issue1, $issue1->events[0], $value->field, null);
        $result2 = $this->repository->setFieldValue($issue6, $issue6->events[0], $value->field, 1234);
        static::assertNotNull($result1);
        static::assertNotNull($result2);

        $this->doctrine->getManager()->flush();

        $this->doctrine->getManager()->refresh($issue1);
        $this->doctrine->getManager()->refresh($issue6);

        $values1 = array_filter($issue1->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::NUMBER);
        $values6 = array_filter($issue6->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::NUMBER);

        static::assertNotEmpty($values1);
        static::assertNotEmpty($values6);

        $value1 = reset($values1);
        $value6 = reset($values6);
        static::assertNull($value1->value);
        static::assertSame(1234, $value6->value);

        static::assertCount($changes + 1, $this->doctrine->getRepository(Change::class)->findAll());

        /** @var Change $change */
        [$change] = $this->doctrine->getRepository(Change::class)->findBy([], ['id' => 'DESC']);

        static::assertSame($value->field, $change->field);
        static::assertSame(5173, $change->oldValue);
        static::assertNull($change->newValue);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetNumberFieldSameValue()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::NUMBER);

        $value = reset($values);
        static::assertSame(5173, $value->value);

        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, 5173);
        static::assertNotNull($result);

        $this->doctrine->getManager()->flush();
        $this->doctrine->getManager()->refresh($issue);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::NUMBER);

        $value = reset($values);
        static::assertSame(5173, $value->value);

        static::assertCount($changes, $this->doctrine->getRepository(Change::class)->findAll());
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetStringFieldValue()
    {
        /** @var Contracts\StringValueRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(StringValue::class);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::STRING);

        $value = reset($values);
        static::assertSame('940059027173b8e8e1e3e874681f012f1f3bcf1d', $repository->find($value->value)->value);

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, 'fb6c40d246aeeb8934884febcd18d19555fd7725');
        static::assertNotNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::STRING);

        $value = reset($values);
        static::assertSame('fb6c40d246aeeb8934884febcd18d19555fd7725', $repository->find($value->value)->value);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetTextFieldValue()
    {
        /** @var Contracts\TextValueRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(TextValue::class);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::TEXT);

        $value = reset($values);
        static::assertSame('Quas sunt reprehenderit vero accusantium.', $repository->find($value->value)->value);

        $result = $this->repository->setFieldValue($issue, $issue->events[0], $value->field, 'Corporis ea amet eligendi fugit.');
        static::assertNotNull($result);

        $values = array_filter($issue->values, fn (FieldValue $fieldValue) => $fieldValue->field->type === FieldType::TEXT);

        $value = reset($values);
        static::assertSame('Corporis ea amet eligendi fugit.', $repository->find($value->value)->value);
    }
}
