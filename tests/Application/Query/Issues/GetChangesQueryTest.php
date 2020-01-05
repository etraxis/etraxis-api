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

use eTraxis\Entity\Change;
use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Issues\Handler\GetChangesHandler
 */
class GetChangesQueryTest extends TransactionalTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $query = new GetChangesQuery([
            'issue' => $issue->id,
        ]);

        /** @var Change[] $changes */
        $changes = $this->queryBus->execute($query);

        self::assertCount(2, $changes);

        self::assertNull($changes[0]->field);
        self::assertNotNull($changes[1]->field);
        self::assertSame('Priority', $changes[1]->field->name);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDeniedAnonymous()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs(null);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $query = new GetChangesQuery([
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
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $query = new GetChangesQuery([
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

        $query = new GetChangesQuery([
            'issue' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->queryBus->execute($query);
    }
}
