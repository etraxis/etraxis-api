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

namespace eTraxis\Application\Query\Issues;

use eTraxis\Entity\Issue;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Issues\Handler\GetIssuesHandler
 */
class GetIssuesQueryTest extends WebTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $this->loginAs('ldoyle@example.com');

        $query = new GetIssuesQuery(new Request());

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(41, $collection->to);
        self::assertSame(42, $collection->total);

        $repository = $this->doctrine->getRepository(Issue::class);

        $expected = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $repository->findAll());

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testByDeveloperB()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testBySupportB()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('vparker@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(17, $collection->to);
        self::assertSame(18, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testByClientB()
    {
        $this->loginAs('aschinner@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     */
    public function testByAuthor()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('lucas.oconnell@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(5, $collection->to);
        self::assertSame(6, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testByResponsible()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Development task 8'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('tmarquardt@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(18, $collection->to);
        self::assertSame(19, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testOffset()
    {
        $expected = [
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Support request 1'],
            ['Molestiae', 'Support request 2'],
            ['Molestiae', 'Support request 3'],
            ['Molestiae', 'Development task 8'],
            ['Molestiae', 'Support request 4'],
            ['Molestiae', 'Support request 5'],
            ['Molestiae', 'Support request 6'],
            ['Excepturi', 'Support request 1'],
            ['Excepturi', 'Support request 2'],
            ['Excepturi', 'Support request 3'],
            ['Excepturi', 'Support request 4'],
            ['Excepturi', 'Support request 5'],
            ['Excepturi', 'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 10;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(10, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = 10;

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testFilterBySubject()
    {
        $expected = [
            ['Molestiae', 'Development task 1'],
            ['Molestiae', 'Development task 2'],
            ['Molestiae', 'Development task 3'],
            ['Molestiae', 'Development task 4'],
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Development task 8'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_SUBJECT => 'aSk',
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs(null);

        $query = new GetIssuesQuery(new Request());

        $this->queryBus->execute($query);
    }
}
