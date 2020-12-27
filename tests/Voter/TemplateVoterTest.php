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
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @coversDefaultClass \eTraxis\Voter\TemplateVoter
 */
class TemplateVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private TemplateRepositoryInterface   $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnsupportedAttribute()
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted('UNKNOWN', $template));
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

        $voter = new TemplateVoter($manager);
        $this->setProperty($voter, 'attributes', ['UNKNOWN' => null]);

        $this->loginAs('admin@example.com');
        static::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($tokenStorage->getToken(), null, ['UNKNOWN']));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new TemplateVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        static::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $project, [TemplateVoter::CREATE_TEMPLATE]));
        static::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::UPDATE_TEMPLATE]));
        static::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::DELETE_TEMPLATE]));
        static::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::LOCK_TEMPLATE]));
        static::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::UNLOCK_TEMPLATE]));
        static::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::GET_PERMISSIONS]));
        static::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::SET_PERMISSIONS]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate()
    {
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        [$templateA] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);
        [$templateD] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateA));
        static::assertTrue($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateD));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateA));
        static::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateD));
    }

    /**
     * @covers ::isLockGranted
     * @covers ::voteOnAttribute
     */
    public function testLock()
    {
        [$templateA, /* skipping */, $templateC] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateA));
        static::assertTrue($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateA));
        static::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateC));
    }

    /**
     * @covers ::isUnlockGranted
     * @covers ::voteOnAttribute
     */
    public function testUnlock()
    {
        [$templateA, /* skipping */, $templateC] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateA));
        static::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateC));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateA));
        static::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateC));
    }

    /**
     * @covers ::isGetPermissionsGranted
     * @covers ::voteOnAttribute
     */
    public function testGetPermissions()
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(TemplateVoter::GET_PERMISSIONS, $template));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(TemplateVoter::GET_PERMISSIONS, $template));
    }

    /**
     * @covers ::isSetPermissionsGranted
     * @covers ::voteOnAttribute
     */
    public function testSetPermissions()
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        static::assertTrue($this->security->isGranted(TemplateVoter::SET_PERMISSIONS, $template));

        $this->loginAs('artem@example.com');
        static::assertFalse($this->security->isGranted(TemplateVoter::SET_PERMISSIONS, $template));
    }
}
