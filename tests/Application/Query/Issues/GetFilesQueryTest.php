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

use eTraxis\Entity\File;
use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Issues\Handler\GetFilesHandler
 */
class GetFilesQueryTest extends TransactionalTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testSuccess()
    {
        $expected = [
            'Beatae nesciunt natus suscipit iure assumenda commodi.docx',
            'Nesciunt nulla sint amet.xslx',
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $query = new GetFilesQuery([
            'issue' => $issue->id,
        ]);

        $files = $this->queryBus->execute($query);

        $actual = array_map(function (File $file) {
            return $file->name;
        }, $files);

        self::assertSame($expected, $actual);
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

        $query = new GetFilesQuery([
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

        $query = new GetFilesQuery([
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

        $query = new GetFilesQuery([
            'issue' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->queryBus->execute($query);
    }
}
