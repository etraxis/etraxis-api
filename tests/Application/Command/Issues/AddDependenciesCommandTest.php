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

namespace eTraxis\Application\Command\Issues;

use eTraxis\Entity\Issue;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Issues\Handler\AddDependenciesHandler::__invoke
 */
class AddDependenciesCommandTest extends TransactionalTestCase
{
    private IssueRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess()
    {
        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $new */
        [/* skipping */, /* skipping */, $new] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $count = count($issue->dependencies);

        $command = new AddDependenciesCommand([
            'issue'        => $issue->id,
            'dependencies' => [
                $existing->id,
                $new->id,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($count + 1, $issue->dependencies);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('jmueller@example.com');

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $new */
        [/* skipping */, /* skipping */, $new] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $command = new AddDependenciesCommand([
            'issue'        => self::UNKNOWN_ENTITY_ID,
            'dependencies' => [
                $existing->id,
                $new->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to add dependencies.');

        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $new */
        [/* skipping */, /* skipping */, $new] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $command = new AddDependenciesCommand([
            'issue'        => $issue->id,
            'dependencies' => [
                $existing->id,
                $new->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownDependencies()
    {
        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $new */
        [/* skipping */, /* skipping */, $new] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        /** @var Issue $forbidden1 */
        [/* skipping */, /* skipping */, $forbidden1] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden2 */
        [/* skipping */, /* skipping */, $forbidden2] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage("Unknown dependencies - {$forbidden1->id},{$forbidden2->id}.");

        $command = new AddDependenciesCommand([
            'issue'        => $issue->id,
            'dependencies' => [
                $existing->id,
                $new->id,
                $forbidden1->id,
                $forbidden2->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }
}
