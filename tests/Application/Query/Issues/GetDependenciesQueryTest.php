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

namespace eTraxis\Application\Query\Issues;

use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Issues\Handler\GetDependenciesHandler
 */
class GetDependenciesQueryTest extends TransactionalTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue = $issue->id;

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(1, $collection->to);
        static::assertSame(2, $collection->total);

        $expected = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $issue->dependencies);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        sort($expected);
        sort($actual);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testByManager()
    {
        $expected = [
            ['Distinctio', 'Development task 8'],
            ['Distinctio', 'Support request 1'],
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_SUBJECT => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(1, $collection->to);
        static::assertSame(2, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testByDeveloper()
    {
        $expected = [
            ['Distinctio', 'Development task 8'],
            ['Distinctio', 'Support request 1'],
        ];

        $this->loginAs('fdooley@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_SUBJECT => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(1, $collection->to);
        static::assertSame(2, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testBySupport()
    {
        $this->loginAs('kschultz@example.com');

        $expected = [
            ['Distinctio', 'Support request 1'],
        ];

        $this->loginAs('tmarquardt@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_SUBJECT => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(0, $collection->to);
        static::assertSame(1, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testByClient()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
        ];

        $this->loginAs('lucas.oconnell@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_SUBJECT => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(0, $collection->to);
        static::assertSame(1, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testOffset()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 1;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_SUBJECT => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(1, $collection->from);
        static::assertSame(1, $collection->to);
        static::assertSame(2, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit()
    {
        $expected = [
            ['Distinctio', 'Development task 8'],
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = 1;

        $query->sort = [
            Issue::JSON_SUBJECT => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(0, $collection->to);
        static::assertSame(2, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testFilterBySubject()
    {
        $expected = [
            ['Distinctio', 'Development task 8'],
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_SUBJECT => 'aSk',
        ];

        $query->sort = [
            Issue::JSON_SUBJECT => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(0, $collection->to);
        static::assertSame(1, $collection->total);

        $actual = array_map(fn (Issue $issue) => [
            $issue->state->template->project->name,
            $issue->subject,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDeniedAnonymous()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs(null);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue = $issue->id;

        $this->queryBus->execute($query);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDeniedPermissions()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('aschinner@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $query = new GetDependenciesQuery(new Request());

        $query->issue = $issue->id;

        $this->queryBus->execute($query);
    }

    /**
     * @covers ::__invoke
     */
    public function testNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('ldoyle@example.com');

        $query = new GetDependenciesQuery(new Request());

        $query->issue = self::UNKNOWN_ENTITY_ID;

        $this->queryBus->execute($query);
    }
}
