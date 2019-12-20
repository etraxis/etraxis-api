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

namespace eTraxis\Application\Command\Issues;

use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Issues\Handler\RemoveDependenciesHandler::__invoke
 */
class RemoveDependenciesCommandTest extends TransactionalTestCase
{
    /**
     * @var \eTraxis\Repository\Contracts\IssueRepositoryInterface
     */
    private $repository;

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

        /** @var Issue $missing */
        [/* skipping */, /* skipping */, $missing] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $count = count($issue->dependencies);

        $command = new RemoveDependenciesCommand([
            'issue'        => $issue->id,
            'dependencies' => [
                $existing->id,
                $missing->id,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($count - 1, $issue->dependencies);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('jmueller@example.com');

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $missing */
        [/* skipping */, /* skipping */, $missing] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $command = new RemoveDependenciesCommand([
            'issue'        => self::UNKNOWN_ENTITY_ID,
            'dependencies' => [
                $existing->id,
                $missing->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to remove dependencies.');

        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $missing */
        [/* skipping */, /* skipping */, $missing] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $command = new RemoveDependenciesCommand([
            'issue'        => $issue->id,
            'dependencies' => [
                $existing->id,
                $missing->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnremovableDependencies()
    {
        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $missing */
        [/* skipping */, /* skipping */, $missing] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        /** @var Issue $forbidden1 */
        [/* skipping */, /* skipping */, $forbidden1] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden2 */
        [/* skipping */, /* skipping */, $forbidden2] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage("Unremovable dependencies - {$forbidden1->id},{$forbidden2->id}.");

        $command = new RemoveDependenciesCommand([
            'issue'        => $issue->id,
            'dependencies' => [
                $existing->id,
                $missing->id,
                $forbidden1->id,
                $forbidden2->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }
}
