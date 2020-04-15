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

use eTraxis\Entity\Template;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Users\Handler\GetNewIssueTemplatesHandler
 */
class GetNewIssueTemplatesQueryTest extends TransactionalTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->loadUserByUsername('ldoyle@example.com');

        /** @var Template $taskC */
        /** @var Template $reqC */
        /** @var Template $reqD */
        [/* skipping */, /* skipping */, $taskC]       = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $reqC, $reqD] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $expected = [
            $taskC,
            $reqC,
            $reqD,
        ];

        $query = new GetNewIssueTemplatesQuery([
            'user' => $user->id,
        ]);

        $collection = $this->queryBus->execute($query);

        self::assertCount(3, $collection);
        self::assertSame($expected, $collection);
    }

    /**
     * @covers ::__invoke
     */
    public function testNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $query = new GetNewIssueTemplatesQuery([
            'user' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->queryBus->execute($query);
    }
}
