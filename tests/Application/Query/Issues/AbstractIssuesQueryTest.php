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
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Issues\Handler\AbstractIssuesHandler
 */
class AbstractIssuesQueryTest extends WebTestCase
{
    /**
     * @covers ::querySearch
     */
    public function testSearch()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
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

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;
        $query->search = 'pOr';

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(19, $collection->to);
        self::assertSame(20, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterById()
    {
        $this->loginAs('ldoyle@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = 1;

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        /** @var Issue $first */
        $first = $collection->data[0];

        $id = (int) mb_substr($first->fullId, mb_strpos($first->fullId, '-') + 1, -1) + 1;

        $expected = range($id * 10, $id * 10 + 9);

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_ID => '-' . mb_substr('00' . $id, -max(2, mb_strlen($id))),
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(10, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return $issue->id;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
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

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByAuthor()
    {
        $expected = [
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Development task 8'],
        ];

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'labshire@example.com']);

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_AUTHOR => $user->id,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByAuthorName()
    {
        $expected = [
            ['Carson Legros', 'Distinctio', 'Support request 2'],
            ['Carson Legros', 'Distinctio', 'Support request 3'],
            ['Carson Legros', 'Distinctio', 'Support request 5'],
            ['Carolyn Hill',  'Molestiae',  'Development task 5'],
            ['Carolyn Hill',  'Molestiae',  'Development task 6'],
            ['Carson Legros', 'Molestiae',  'Support request 2'],
            ['Carson Legros', 'Molestiae',  'Support request 3'],
            ['Carson Legros', 'Molestiae',  'Support request 5'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_AUTHOR_NAME => 'caR',
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->author->fullname,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByProject()
    {
        $expected = [
            ['Molestiae', 'Development task 1'],
            ['Molestiae', 'Development task 2'],
            ['Molestiae', 'Development task 3'],
            ['Molestiae', 'Development task 4'],
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
        ];

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Molestiae']);

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_PROJECT => $project->id,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(13, $collection->to);
        self::assertSame(14, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByProjectName()
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
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_PROJECT_NAME => 'Ti',
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(19, $collection->to);
        self::assertSame(20, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByTemplate()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
        ];

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_TEMPLATE => $template->id,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(5, $collection->to);
        self::assertSame(6, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByTemplateName()
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
            Issue::JSON_TEMPLATE_NAME => 'vELo',
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByState()
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
        ];

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_STATE => $state->id,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByStateName()
    {
        $expected = [
            ['Completed',  'Molestiae',  'Development task 1'],
            ['Completed',  'Molestiae',  'Development task 3'],
            ['Submitted',  'Distinctio', 'Support request 6'],
            ['Duplicated', 'Molestiae',  'Development task 4'],
            ['Duplicated', 'Molestiae',  'Development task 7'],
            ['Submitted',  'Molestiae',  'Support request 6'],
            ['Submitted',  'Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_STATE_NAME => 'tED',
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(6, $collection->to);
        self::assertSame(7, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->state->name,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByResponsible()
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Development task 8'],
        ];

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_RESPONSIBLE => $user->id,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByResponsibleNull()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_RESPONSIBLE => null,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(15, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByResponsibleName()
    {
        $expected = [
            ['Jarrell Kiehn',   'Distinctio', 'Support request 4'],
            ['Tracy Marquardt', 'Distinctio', 'Support request 5'],
            ['Tracy Marquardt', 'Molestiae',  'Support request 4'],
            ['Tracy Marquardt', 'Excepturi',  'Support request 2'],
            ['Carter Batz',     'Excepturi',  'Support request 4'],
            ['Carter Batz',     'Excepturi',  'Support request 5'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_RESPONSIBLE_NAME => 'AR',
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(5, $collection->to);
        self::assertSame(6, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->responsible->fullname,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByIsClonedYes()
    {
        $expected = [
            ['Molestiae', 'Development task 5'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_IS_CLONED => true,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByIsClonedNo()
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

        $query->filter = [
            Issue::JSON_IS_CLONED => false,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
        self::assertSame(25, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByAge()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_AGE => $issue->age,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(6, $collection->to);
        self::assertSame(7, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByIsCriticalYes()
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_IS_CRITICAL => true,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(11, $collection->to);
        self::assertSame(12, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByIsCriticalNo()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_IS_CRITICAL => false,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(13, $collection->to);
        self::assertSame(14, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByIsSuspendedYes()
    {
        $expected = [
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 5'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_IS_SUSPENDED => true,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByIsSuspendedNo()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_IS_SUSPENDED => false,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(21, $collection->to);
        self::assertSame(22, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByIsClosedYes()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_IS_CLOSED => true,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(10, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByIsClosedNo()
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 2'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_IS_CLOSED => false,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(15, $collection->to);
        self::assertSame(16, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryFilter
     */
    public function testFilterByDependency()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Development task 8'],
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_DEPENDENCY => $issue->id,
        ];

        $query->sort = [
            Issue::JSON_ID => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortById()
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

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortBySubject()
    {
        $expected = [
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Development task 8'],
            ['Distinctio', 'Support request 1'],
            ['Molestiae',  'Support request 1'],
            ['Excepturi',  'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Molestiae',  'Support request 2'],
            ['Excepturi',  'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Molestiae',  'Support request 4'],
            ['Excepturi',  'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_SUBJECT => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID      => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortByCreatedAt()
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
            ['Molestiae',  'Support request 6'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
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
            Issue::JSON_CREATED_AT => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID         => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortByChangedAt()
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
            ['Molestiae',  'Support request 6'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
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
            Issue::JSON_CHANGED_AT => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID         => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortByClosedAt()
    {
        $expected = [
            // opened
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 2'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
            // closed
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_CLOSED_AT => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID        => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortByAuthor()
    {
        $expected = [
            ['Ansel Koepp',      'Molestiae',  'Development task 3'],
            ['Carolyn Hill',     'Molestiae',  'Development task 5'],
            ['Carolyn Hill',     'Molestiae',  'Development task 6'],
            ['Carson Legros',    'Distinctio', 'Support request 2'],
            ['Carson Legros',    'Distinctio', 'Support request 3'],
            ['Carson Legros',    'Distinctio', 'Support request 5'],
            ['Carson Legros',    'Molestiae',  'Support request 2'],
            ['Carson Legros',    'Molestiae',  'Support request 3'],
            ['Carson Legros',    'Molestiae',  'Support request 5'],
            ['Derrick Tillman',  'Molestiae',  'Support request 4'],
            ['Derrick Tillman',  'Excepturi',  'Support request 4'],
            ['Dorcas Ernser',    'Molestiae',  'Development task 2'],
            ['Jarrell Kiehn',    'Molestiae',  'Development task 4'],
            ['Jeramy Mueller',   'Distinctio', 'Support request 4'],
            ['Jeramy Mueller',   'Excepturi',  'Support request 2'],
            ['Jeramy Mueller',   'Excepturi',  'Support request 3'],
            ['Jeramy Mueller',   'Excepturi',  'Support request 5'],
            ['Leland Doyle',     'Molestiae',  'Development task 1'],
            ['Lola Abshire',     'Molestiae',  'Development task 7'],
            ['Lola Abshire',     'Molestiae',  'Development task 8'],
            ['Lucas O\'Connell', 'Distinctio', 'Support request 1'],
            ['Lucas O\'Connell', 'Distinctio', 'Support request 6'],
            ['Lucas O\'Connell', 'Molestiae',  'Support request 1'],
            ['Lucas O\'Connell', 'Molestiae',  'Support request 6'],
            ['Lucas O\'Connell', 'Excepturi',  'Support request 1'],
            ['Lucas O\'Connell', 'Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_AUTHOR => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID     => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->author->fullname,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortByProject()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
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
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_PROJECT => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID      => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortByTemplate()
    {
        $expected = [
            ['Development', 'Molestiae',  'Development task 1'],
            ['Development', 'Molestiae',  'Development task 2'],
            ['Development', 'Molestiae',  'Development task 3'],
            ['Development', 'Molestiae',  'Development task 4'],
            ['Development', 'Molestiae',  'Development task 5'],
            ['Development', 'Molestiae',  'Development task 6'],
            ['Development', 'Molestiae',  'Development task 7'],
            ['Development', 'Molestiae',  'Development task 8'],
            ['Support',     'Distinctio', 'Support request 1'],
            ['Support',     'Distinctio', 'Support request 2'],
            ['Support',     'Distinctio', 'Support request 3'],
            ['Support',     'Distinctio', 'Support request 4'],
            ['Support',     'Distinctio', 'Support request 5'],
            ['Support',     'Distinctio', 'Support request 6'],
            ['Support',     'Molestiae',  'Support request 1'],
            ['Support',     'Molestiae',  'Support request 2'],
            ['Support',     'Molestiae',  'Support request 3'],
            ['Support',     'Molestiae',  'Support request 4'],
            ['Support',     'Molestiae',  'Support request 5'],
            ['Support',     'Molestiae',  'Support request 6'],
            ['Support',     'Excepturi',  'Support request 1'],
            ['Support',     'Excepturi',  'Support request 2'],
            ['Support',     'Excepturi',  'Support request 3'],
            ['Support',     'Excepturi',  'Support request 4'],
            ['Support',     'Excepturi',  'Support request 5'],
            ['Support',     'Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_TEMPLATE => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID       => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->state->template->name,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortByState()
    {
        $expected = [
            ['Assigned',   'Molestiae',  'Development task 2'],
            ['Assigned',   'Molestiae',  'Development task 8'],
            ['Completed',  'Molestiae',  'Development task 1'],
            ['Completed',  'Molestiae',  'Development task 3'],
            ['Duplicated', 'Molestiae',  'Development task 4'],
            ['Duplicated', 'Molestiae',  'Development task 7'],
            ['New',        'Molestiae',  'Development task 5'],
            ['New',        'Molestiae',  'Development task 6'],
            ['Opened',     'Distinctio', 'Support request 2'],
            ['Opened',     'Distinctio', 'Support request 4'],
            ['Opened',     'Distinctio', 'Support request 5'],
            ['Opened',     'Molestiae',  'Support request 2'],
            ['Opened',     'Molestiae',  'Support request 4'],
            ['Opened',     'Molestiae',  'Support request 5'],
            ['Opened',     'Excepturi',  'Support request 2'],
            ['Opened',     'Excepturi',  'Support request 4'],
            ['Opened',     'Excepturi',  'Support request 5'],
            ['Resolved',   'Distinctio', 'Support request 1'],
            ['Resolved',   'Distinctio', 'Support request 3'],
            ['Resolved',   'Molestiae',  'Support request 1'],
            ['Resolved',   'Molestiae',  'Support request 3'],
            ['Resolved',   'Excepturi',  'Support request 1'],
            ['Resolved',   'Excepturi',  'Support request 3'],
            ['Submitted',  'Distinctio', 'Support request 6'],
            ['Submitted',  'Molestiae',  'Support request 6'],
            ['Submitted',  'Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_STATE => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID    => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->state->name,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortByResponsible()
    {
        $expected = [
            [null,               'Distinctio', 'Support request 1'],
            [null,               'Distinctio', 'Support request 3'],
            [null,               'Molestiae',  'Development task 1'],
            [null,               'Molestiae',  'Development task 3'],
            [null,               'Distinctio', 'Support request 6'],
            [null,               'Molestiae',  'Development task 4'],
            [null,               'Molestiae',  'Development task 5'],
            [null,               'Molestiae',  'Development task 6'],
            [null,               'Molestiae',  'Development task 7'],
            [null,               'Molestiae',  'Support request 1'],
            [null,               'Molestiae',  'Support request 3'],
            [null,               'Molestiae',  'Support request 6'],
            [null,               'Excepturi',  'Support request 1'],
            [null,               'Excepturi',  'Support request 3'],
            [null,               'Excepturi',  'Support request 6'],
            ['Ansel Koepp',      'Molestiae',  'Development task 2'],
            ['Carter Batz',      'Excepturi',  'Support request 4'],
            ['Carter Batz',      'Excepturi',  'Support request 5'],
            ['Jarrell Kiehn',    'Distinctio', 'Support request 4'],
            ['Kailyn Bahringer', 'Molestiae',  'Support request 5'],
            ['Nikko Hills',      'Distinctio', 'Support request 2'],
            ['Nikko Hills',      'Molestiae',  'Support request 2'],
            ['Nikko Hills',      'Molestiae',  'Development task 8'],
            ['Tracy Marquardt',  'Distinctio', 'Support request 5'],
            ['Tracy Marquardt',  'Molestiae',  'Support request 4'],
            ['Tracy Marquardt',  'Excepturi',  'Support request 2'],
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_RESPONSIBLE => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID          => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->responsible === null ? null : $issue->responsible->fullname,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::queryOrder
     */
    public function testSortByAge()
    {
        $expected = [
            ['Molestiae',  'Development task 4'],   //    1 day
            ['Distinctio', 'Support request 1'],    //    2 days
            ['Distinctio', 'Support request 3'],    //    2 days
            ['Molestiae',  'Development task 7'],   //    2 days
            ['Molestiae',  'Support request 1'],    //    2 days
            ['Molestiae',  'Support request 3'],    //    2 days
            ['Excepturi',  'Support request 1'],    //    2 days
            ['Excepturi',  'Support request 3'],    //    2 days
            ['Molestiae',  'Development task 1'],   //    3 days
            ['Molestiae',  'Development task 3'],   //    5 days
            ['Excepturi',  'Support request 6'],    //  345 days
            ['Excepturi',  'Support request 5'],    //  348 days
            ['Excepturi',  'Support request 4'],    //  366 days
            ['Excepturi',  'Support request 2'],    //  410 days
            ['Molestiae',  'Support request 5'],    //  482 days
            ['Molestiae',  'Support request 4'],    //  494 days
            ['Molestiae',  'Support request 6'],    //  512 days
            ['Molestiae',  'Development task 8'],   //  518 days
            ['Molestiae',  'Support request 2'],    //  553 days
            ['Molestiae',  'Development task 6'],   //  606 days
            ['Molestiae',  'Development task 5'],   //  661 days
            ['Distinctio', 'Support request 6'],    //  693 days
            ['Molestiae',  'Development task 2'],   //  725 days
            ['Distinctio', 'Support request 5'],    //  933 days
            ['Distinctio', 'Support request 4'],    //  946 days
            ['Distinctio', 'Support request 2'],    // 1057 days
        ];

        $this->loginAs('amarvin@example.com');

        $query = new GetIssuesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetIssuesQuery::MAX_LIMIT;

        $query->sort = [
            Issue::JSON_AGE => GetIssuesQuery::SORT_ASC,
            Issue::JSON_ID  => GetIssuesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }
}
