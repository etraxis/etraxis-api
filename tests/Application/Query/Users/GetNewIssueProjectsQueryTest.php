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

namespace eTraxis\Application\Query\Users;

use eTraxis\Entity\Project;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Users\Handler\GetNewIssueProjectsHandler
 */
class GetNewIssueProjectsQueryTest extends TransactionalTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(User::class);

        $user = $repository->loadUserByUsername('ldoyle@example.com');

        /** @var Project $projectC */
        /** @var Project $projectD */
        $projectC = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Excepturi']);
        $projectD = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Presto']);

        $expected = [
            $projectC,
            $projectD,
        ];

        $query = new GetNewIssueProjectsQuery([
            'user' => $user->id,
        ]);

        $collection = $this->queryBus->execute($query);

        self::assertCount(2, $collection);
        self::assertSame($expected, $collection);
    }

    /**
     * @covers ::__invoke
     */
    public function testNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $query = new GetNewIssueProjectsQuery([
            'user' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->queryBus->execute($query);
    }
}
