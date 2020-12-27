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

use eTraxis\Entity\Comment;
use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Issues\Handler\GetCommentsHandler
 */
class GetCommentsQueryTest extends TransactionalTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testSuccessWithPrivate()
    {
        $expected = [
            'Assumenda dolor tempora nisi tempora tempore.',
            'Ut ipsum explicabo iste sequi dignissimos.',
            'Natus excepturi est eaque nostrum non.',
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $query = new GetCommentsQuery([
            'issue' => $issue->id,
        ]);

        $comments = $this->queryBus->execute($query);

        static::assertCount(3, $comments);

        $actual = array_map(fn (Comment $comment) => mb_substr($comment->body, 0, mb_strpos($comment->body, '.') + 1), $comments);

        sort($expected);
        sort($actual);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testSuccessNoPrivate()
    {
        $expected = [
            'Assumenda dolor tempora nisi tempora tempore.',
            'Natus excepturi est eaque nostrum non.',
        ];

        $this->loginAs('fdooley@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $query = new GetCommentsQuery([
            'issue' => $issue->id,
        ]);

        $comments = $this->queryBus->execute($query);

        static::assertCount(2, $comments);

        $actual = array_map(fn (Comment $comment) => mb_substr($comment->body, 0, mb_strpos($comment->body, '.') + 1), $comments);

        sort($expected);
        sort($actual);

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
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $query = new GetCommentsQuery([
            'issue' => $issue->id,
        ]);

        $this->queryBus->execute($query);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDeniedPermissions()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('lucas.oconnell@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $query = new GetCommentsQuery([
            'issue' => $issue->id,
        ]);

        $this->queryBus->execute($query);
    }

    /**
     * @covers ::__invoke
     */
    public function testNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('ldoyle@example.com');

        $query = new GetCommentsQuery([
            'issue' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->queryBus->execute($query);
    }
}
