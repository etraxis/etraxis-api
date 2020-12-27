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

namespace eTraxis\Voter;

use eTraxis\Entity\Project;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\ProjectRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @coversDefaultClass \eTraxis\Voter\ProjectVoter
 */
class ProjectVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private ProjectRepositoryInterface    $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Project::class);
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnsupportedAttribute()
    {
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted('UNKNOWN', $project));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnexpectedAttribute()
    {
        /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage */
        $tokenStorage = self::$container->get('security.token_storage');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new ProjectVoter($manager);
        $this->setProperty($voter, 'attributes', ['UNKNOWN' => null]);

        $this->loginAs('admin@example.com');
        static::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($tokenStorage->getToken(), null, ['UNKNOWN']));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new ProjectVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        static::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, null, [ProjectVoter::CREATE_PROJECT]));
        static::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::UPDATE_PROJECT]));
        static::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::DELETE_PROJECT]));
        static::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::SUSPEND_PROJECT]));
        static::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::RESUME_PROJECT]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate()
    {
        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(ProjectVoter::CREATE_PROJECT));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(ProjectVoter::CREATE_PROJECT));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $project));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $project));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        $projectA = $this->repository->findOneBy(['name' => 'Distinctio']);
        $projectD = $this->repository->findOneBy(['name' => 'Presto']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectA));
        static::assertTrue($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectD));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectA));
        static::assertFalse($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectD));
    }

    /**
     * @covers ::isSuspendGranted
     * @covers ::voteOnAttribute
     */
    public function testSuspend()
    {
        $projectA = $this->repository->findOneBy(['name' => 'Distinctio']);
        $projectB = $this->repository->findOneBy(['name' => 'Molestiae']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectA));
        static::assertTrue($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectB));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectA));
        static::assertFalse($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectB));
    }

    /**
     * @covers ::isResumeGranted
     * @covers ::voteOnAttribute
     */
    public function testResume()
    {
        $projectA = $this->repository->findOneBy(['name' => 'Distinctio']);
        $projectB = $this->repository->findOneBy(['name' => 'Molestiae']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectA));
        static::assertFalse($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectB));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectA));
        static::assertFalse($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectB));
    }
}
