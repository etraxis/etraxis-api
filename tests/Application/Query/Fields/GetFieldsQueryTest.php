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

namespace eTraxis\Application\Query\Fields;

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Fields\Handler\GetFieldsHandler
 */
class GetFieldsQueryTest extends WebTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(39, $collection->to);
        self::assertSame(40, $collection->total);

        $repository = $this->doctrine->getRepository(Field::class);

        $expected = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $repository->findBy(['removedAt' => null]));

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testOffset()
    {
        $expected = [
            'Effort',
            'Issue ID',
            'New feature',
            'Priority',
            'Test coverage',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 35;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(35, $collection->from);
        self::assertSame(39, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => $field->name, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit()
    {
        $expected = [
            'Commit ID',
            'Delta',
            'Description',
            'Details',
            'Due date',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 5;

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(4, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => $field->name, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch()
    {
        $expected = [
            ['Effort',   'Distinctio'],
            ['Priority', 'Distinctio'],
            ['Effort',   'Excepturi'],
            ['Priority', 'Excepturi'],
            ['Effort',   'Molestiae'],
            ['Priority', 'Molestiae'],
            ['Effort',   'Presto'],
            ['Priority', 'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;
        $query->search = 'oR';

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProject()
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Delta',         'Distinctio'],
            ['Description',   'Distinctio'],
            ['Details',       'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Effort',        'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
        ];

        $this->loginAs('admin@example.com');

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_PROJECT => $project->id,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(10, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProjectNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_PROJECT => null,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTemplate()
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Delta',         'Distinctio'],
            ['Description',   'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Effort',        'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
        ];

        $this->loginAs('admin@example.com');

        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development']);

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_TEMPLATE => $template->id,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTemplateNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_TEMPLATE => null,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByState()
    {
        $expected = [
            ['Description', 'Distinctio'],
            ['New feature', 'Distinctio'],
            ['Priority',    'Distinctio'],
        ];

        $this->loginAs('admin@example.com');

        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New']);

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_STATE => $state->id,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByStateNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_STATE => null,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByName()
    {
        $expected = [
            ['Due date',    'Distinctio'],
            ['New feature', 'Distinctio'],
            ['Due date',    'Excepturi'],
            ['New feature', 'Excepturi'],
            ['Due date',    'Molestiae'],
            ['New feature', 'Molestiae'],
            ['Due date',    'Presto'],
            ['New feature', 'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_NAME => 'aT',
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByNameNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_NAME => null,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByType()
    {
        $expected = [
            ['Description', 'Distinctio'],
            ['Details',     'Distinctio'],
            ['Description', 'Excepturi'],
            ['Details',     'Excepturi'],
            ['Description', 'Molestiae'],
            ['Details',     'Molestiae'],
            ['Description', 'Presto'],
            ['Details',     'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_TYPE => FieldType::TEXT,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTypeNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_TYPE => null,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescription()
    {
        $expected = [
            ['Delta', 'Distinctio'],
            ['Delta', 'Excepturi'],
            ['Delta', 'Molestiae'],
            ['Delta', 'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_DESCRIPTION => 'LoC',
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescriptionNull()
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Description',   'Distinctio'],
            ['Details',       'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
            ['Commit ID',     'Excepturi'],
            ['Description',   'Excepturi'],
            ['Details',       'Excepturi'],
            ['Due date',      'Excepturi'],
            ['Issue ID',      'Excepturi'],
            ['New feature',   'Excepturi'],
            ['Priority',      'Excepturi'],
            ['Test coverage', 'Excepturi'],
            ['Commit ID',     'Molestiae'],
            ['Description',   'Molestiae'],
            ['Details',       'Molestiae'],
            ['Due date',      'Molestiae'],
            ['Issue ID',      'Molestiae'],
            ['New feature',   'Molestiae'],
            ['Priority',      'Molestiae'],
            ['Test coverage', 'Molestiae'],
            ['Commit ID',     'Presto'],
            ['Description',   'Presto'],
            ['Details',       'Presto'],
            ['Due date',      'Presto'],
            ['Issue ID',      'Presto'],
            ['New feature',   'Presto'],
            ['Priority',      'Presto'],
            ['Test coverage', 'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_DESCRIPTION => null,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(31, $collection->to);
        self::assertSame(32, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByPosition()
    {
        $expected = [
            ['Effort',      'Distinctio'],
            ['New feature', 'Distinctio'],
            ['Effort',      'Excepturi'],
            ['New feature', 'Excepturi'],
            ['Effort',      'Molestiae'],
            ['New feature', 'Molestiae'],
            ['Effort',      'Presto'],
            ['New feature', 'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_POSITION => 3,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByPositionNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_POSITION => null,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByRequired()
    {
        $expected = [
            ['Delta',    'Distinctio'],
            ['Details',  'Distinctio'],
            ['Effort',   'Distinctio'],
            ['Issue ID', 'Distinctio'],
            ['Priority', 'Distinctio'],
            ['Delta',    'Excepturi'],
            ['Details',  'Excepturi'],
            ['Effort',   'Excepturi'],
            ['Issue ID', 'Excepturi'],
            ['Priority', 'Excepturi'],
            ['Delta',    'Molestiae'],
            ['Details',  'Molestiae'],
            ['Effort',   'Molestiae'],
            ['Issue ID', 'Molestiae'],
            ['Priority', 'Molestiae'],
            ['Delta',    'Presto'],
            ['Details',  'Presto'],
            ['Effort',   'Presto'],
            ['Issue ID', 'Presto'],
            ['Priority', 'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetFieldsQuery::MAX_LIMIT;

        $query->filter = [
            Field::JSON_REQUIRED => true,
        ];

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(19, $collection->to);
        self::assertSame(20, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByProject()
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Delta',         'Distinctio'],
            ['Description',   'Distinctio'],
            ['Details',       'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Effort',        'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
            ['Commit ID',     'Excepturi'],
            ['Delta',         'Excepturi'],
            ['Description',   'Excepturi'],
            ['Details',       'Excepturi'],
            ['Due date',      'Excepturi'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 15;

        $query->sort = [
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByTemplate()
    {
        $expected = [
            ['Details',     'Distinctio'],
            ['Details',     'Excepturi'],
            ['Details',     'Molestiae'],
            ['Details',     'Presto'],
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
            ['Delta',       'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 15;

        $query->sort = [
            Field::JSON_TEMPLATE => GetFieldsQuery::SORT_DESC,
            Field::JSON_NAME     => GetFieldsQuery::SORT_ASC,
            Field::JSON_PROJECT  => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByState()
    {
        $expected = [
            ['Due date',  'Distinctio'],
            ['Due date',  'Excepturi'],
            ['Due date',  'Molestiae'],
            ['Due date',  'Presto'],
            ['Commit ID', 'Distinctio'],
            ['Commit ID', 'Excepturi'],
            ['Commit ID', 'Molestiae'],
            ['Commit ID', 'Presto'],
            ['Delta',     'Distinctio'],
            ['Delta',     'Excepturi'],
            ['Delta',     'Molestiae'],
            ['Delta',     'Presto'],
            ['Effort',    'Distinctio'],
            ['Effort',    'Excepturi'],
            ['Effort',    'Molestiae'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 15;

        $query->sort = [
            Field::JSON_STATE   => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByName()
    {
        $expected = [
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
            ['Delta',       'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
            ['Description', 'Presto'],
            ['Details',     'Distinctio'],
            ['Details',     'Excepturi'],
            ['Details',     'Molestiae'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 15;

        $query->sort = [
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByType()
    {
        $expected = [
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
            ['Description', 'Presto'],
            ['Details',     'Distinctio'],
            ['Details',     'Excepturi'],
            ['Details',     'Molestiae'],
            ['Details',     'Presto'],
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 15;

        $query->sort = [
            Field::JSON_TYPE    => GetFieldsQuery::SORT_DESC,
            Field::JSON_NAME    => GetFieldsQuery::SORT_ASC,
            Field::JSON_PROJECT => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByDescription()
    {
        $expected = [
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
            ['Delta',       'Presto'],
            ['Effort',      'Distinctio'],
            ['Effort',      'Excepturi'],
            ['Effort',      'Molestiae'],
            ['Effort',      'Presto'],
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 15;

        $query->sort = [
            Field::JSON_DESCRIPTION => GetFieldsQuery::SORT_DESC,
            Field::JSON_NAME        => GetFieldsQuery::SORT_ASC,
            Field::JSON_PROJECT     => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByPosition()
    {
        $expected = [
            ['Test coverage', 'Distinctio'],
            ['Test coverage', 'Excepturi'],
            ['Test coverage', 'Molestiae'],
            ['Test coverage', 'Presto'],
            ['Effort',        'Distinctio'],
            ['Effort',        'Excepturi'],
            ['Effort',        'Molestiae'],
            ['Effort',        'Presto'],
            ['New feature',   'Distinctio'],
            ['New feature',   'Excepturi'],
            ['New feature',   'Molestiae'],
            ['New feature',   'Presto'],
            ['Delta',         'Distinctio'],
            ['Delta',         'Excepturi'],
            ['Delta',         'Molestiae'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 15;

        $query->sort = [
            Field::JSON_POSITION => GetFieldsQuery::SORT_DESC,
            Field::JSON_NAME     => GetFieldsQuery::SORT_ASC,
            Field::JSON_PROJECT  => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByRequired()
    {
        $expected = [
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
            ['Description', 'Presto'],
            ['Due date',    'Distinctio'],
            ['Due date',    'Excepturi'],
            ['Due date',    'Molestiae'],
            ['Due date',    'Presto'],
            ['New feature', 'Distinctio'],
            ['New feature', 'Excepturi'],
            ['New feature', 'Molestiae'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetFieldsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 15;

        $query->sort = [
            Field::JSON_REQUIRED => GetFieldsQuery::SORT_ASC,
            Field::JSON_NAME     => GetFieldsQuery::SORT_ASC,
            Field::JSON_PROJECT  => GetFieldsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(fn (Field $field) => [
            $field->name,
            $field->state->template->project->name,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        $query = new GetFieldsQuery(new Request());

        $this->queryBus->execute($query);
    }
}
