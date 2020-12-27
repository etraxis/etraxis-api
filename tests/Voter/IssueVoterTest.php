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

use eTraxis\Application\Seconds;
use eTraxis\Entity\Issue;
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @coversDefaultClass \eTraxis\Voter\IssueVoter
 */
class IssueVoterTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private IssueRepositoryInterface      $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testUnsupportedAttribute()
    {
        [$issue] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->loginAs('lucas.oconnell@example.com');
        static::assertFalse($this->security->isGranted('UNKNOWN', $issue));
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

        $voter = new IssueVoter($manager, 10);
        $this->setProperty($voter, 'attributes', ['UNKNOWN' => null]);

        $this->loginAs('lucas.oconnell@example.com');
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($tokenStorage->getToken(), null, ['UNKNOWN']));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new IssueVoter($manager, 10);
        $token = new AnonymousToken('', 'anon.');

        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        [$issue1] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);
        [$issue2] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);
        [$issue5] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);
        [$issue6] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue1, [IssueVoter::VIEW_ISSUE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $template, [IssueVoter::CREATE_ISSUE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue1, [IssueVoter::UPDATE_ISSUE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue1, [IssueVoter::DELETE_ISSUE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue6, [IssueVoter::CHANGE_STATE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::REASSIGN_ISSUE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue6, [IssueVoter::SUSPEND_ISSUE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue5, [IssueVoter::RESUME_ISSUE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::ADD_PUBLIC_COMMENT]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::ADD_PRIVATE_COMMENT]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::READ_PRIVATE_COMMENT]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::ATTACH_FILE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::DELETE_FILE]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::ADD_DEPENDENCY]));
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::REMOVE_DEPENDENCY]));
    }

    /**
     * @covers ::hasGroupPermission
     * @covers ::hasRolePermission
     * @covers ::isViewGranted
     * @covers ::voteOnAttribute
     */
    public function testViewByAuthor()
    {
        [$issue1] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);
        [$issue2] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $this->loginAs('lucas.oconnell@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue1));
        static::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue2));
    }

    /**
     * @covers ::hasGroupPermission
     * @covers ::hasRolePermission
     * @covers ::isViewGranted
     * @covers ::voteOnAttribute
     */
    public function testViewByResponsible()
    {
        [$issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $this->loginAs('nhills@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));

        $this->loginAs('jkiehn@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));
    }

    /**
     * @covers ::hasGroupPermission
     * @covers ::hasRolePermission
     * @covers ::isViewGranted
     * @covers ::voteOnAttribute
     */
    public function testViewByLocalGroup()
    {
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->loginAs('labshire@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));

        $this->loginAs('jkiehn@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));
    }

    /**
     * @covers ::hasGroupPermission
     * @covers ::hasRolePermission
     * @covers ::isViewGranted
     * @covers ::voteOnAttribute
     */
    public function testViewByGlobalGroup()
    {
        [$issue] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->loginAs('labshire@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));

        $this->loginAs('clegros@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));
    }

    /**
     * @covers ::hasGroupPermission
     * @covers ::hasRolePermission
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$templateA, $templateB, $templateC] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        // Template D doesn't have initial state.
        [$templateD] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'DESC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateA));
        static::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateB));
        static::assertTrue($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateC));
        static::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateD));

        $this->loginAs('lucas.oconnell@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateC));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $suspended));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $createdByDev3));
        static::assertTrue($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $assignedToDev3));

        /** @var Issue $issueC */
        $issueC->template->frozenTime = 1;

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueC));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $suspended));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $createdByDev3));
        static::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $assignedToDev3));

        /** @var Issue $issueC */
        $issueC->template->frozenTime = 1;

        $this->loginAs('ldoyle@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueC));
    }

    /**
     * @covers ::isChangeStateGranted
     * @covers ::voteOnAttribute
     */
    public function testChangeState()
    {
        // Template B is locked, template C is not.
        // Project A is suspended.
        /** @var Issue $issueC */
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $dependant] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $closed]            = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $createdByClient]   = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToSupport] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        // Logging as a manager of all projects (A, B, C, D).
        $this->loginAs('ldoyle@example.com');
        // Project is suspended.
        static::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueA));
        // Template is locked.
        static::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueB));
        // Everything is OK.
        static::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueC));
        // Issue is suspended.
        static::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $suspended));
        // The issue has unclosed dependencies, but it can be moved to an intermediate state, so it's OK.
        static::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $dependant));

        // Logging as a client of projects B and C.
        $this->loginAs('dtillman@example.com');
        // Everything is OK, but the user doesn't have permissions.
        static::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueC));
        // Everything is OK, the user is the author.
        static::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $createdByClient));

        // Logging as a support engineer of projects A and C.
        $this->loginAs('cbatz@example.com');
        // Everything is OK, but the user doesn't have permissions.
        static::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueC));
        // Everything is OK, the user is the current responsible.
        static::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $assignedToSupport));

        // Logging as a client of projects A, B, and C.
        $this->loginAs('lucas.oconnell@example.com');
        // Everything is OK, but the user doesn't have permissions.
        static::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueC));
        // The issue has unclosed dependencies, so it can't be moved to a final state
        // (this is the only state this user can use on the issue).
        static::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $dependant));
        // Everything is OK, but the issue is closed and frozen.
        static::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $closed));
        // Everything is OK (the issue is not frozen anymore).
        $issueC->template->frozenTime = null;
        static::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $closed));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isReassignGranted
     * @covers ::voteOnAttribute
     */
    public function testReassign()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $unassigned] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $unassigned));

        $this->loginAs('dquigley@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $createdByDev2));
        static::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $assignedToDev3));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $createdByDev2));
        static::assertTrue($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $assignedToDev3));

        /** @var Issue $issueC */
        $issueC->suspend(time() + Seconds::ONE_DAY);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issueC));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isSuspendGranted
     * @covers ::voteOnAttribute
     */
    public function testSuspend()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $suspended));
        static::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $closed));

        $this->loginAs('dquigley@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $createdByDev2));
        static::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $assignedToDev3));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $createdByDev2));
        static::assertTrue($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $assignedToDev3));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isResumeGranted
     * @covers ::voteOnAttribute
     */
    public function testResume()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $resumed] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]  = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $createdByDev2 */
        $createdByDev2->suspend(time() + Seconds::ONE_DAY);

        /** @var Issue $assignedToDev3 */
        $assignedToDev3->suspend(time() + Seconds::ONE_DAY);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $resumed));
        static::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $closed));

        $this->loginAs('dquigley@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::RESUME_ISSUE, $createdByDev2));
        static::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $assignedToDev3));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $createdByDev2));
        static::assertTrue($this->security->isGranted(IssueVoter::RESUME_ISSUE, $assignedToDev3));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isAddPublicCommentGranted
     * @covers ::voteOnAttribute
     */
    public function testAddPublicComment()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $suspended));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $createdByDev3));
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $assignedToDev3));

        /** @var Issue $closed */
        $this->loginAs('ldoyle@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $closed));
        $closed->template->frozenTime = 1;
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $closed));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isAddPrivateCommentGranted
     * @covers ::voteOnAttribute
     */
    public function testAddPrivateComment()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $suspended));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $createdByDev3));
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $assignedToDev3));

        /** @var Issue $closed */
        $this->loginAs('ldoyle@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $closed));
        $closed->template->frozenTime = 1;
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $closed));
    }

    /**
     * @covers ::hasGroupPermission
     * @covers ::hasRolePermission
     * @covers ::isReadPrivateCommentGranted
     * @covers ::voteOnAttribute
     */
    public function testReadPrivateComment()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issueA));
        static::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $suspended));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $createdByDev3));
        static::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $assignedToDev3));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isAttachFileGranted
     * @covers ::voteOnAttribute
     */
    public function testAttachFile()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::ATTACH_FILE, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $suspended));

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();
        $voter   = new IssueVoter($manager, 0);
        $token   = $this->client->getContainer()->get('security.token_storage')->getToken();
        static::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issueC, [IssueVoter::ATTACH_FILE]));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::ATTACH_FILE, $createdByDev3));
        static::assertTrue($this->security->isGranted(IssueVoter::ATTACH_FILE, $assignedToDev3));

        /** @var Issue $closed */
        $this->loginAs('ldoyle@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::ATTACH_FILE, $closed));
        $closed->template->frozenTime = 1;
        static::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $closed));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isDeleteFileGranted
     * @covers ::voteOnAttribute
     */
    public function testDeleteFile()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::DELETE_FILE, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $suspended));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::DELETE_FILE, $createdByDev3));
        static::assertTrue($this->security->isGranted(IssueVoter::DELETE_FILE, $assignedToDev3));

        /** @var Issue $closed */
        $this->loginAs('ldoyle@example.com');
        static::assertTrue($this->security->isGranted(IssueVoter::DELETE_FILE, $closed));
        $closed->template->frozenTime = 1;
        static::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $closed));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isAddDependencyGranted
     * @covers ::voteOnAttribute
     */
    public function testAddDependency()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $suspended));
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $closed));

        $this->loginAs('dquigley@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $createdByDev2));
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $assignedToDev3));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $createdByDev2));
        static::assertTrue($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $assignedToDev3));
    }

    /**
     * @covers ::hasPermission
     * @covers ::isRemoveDependencyGranted
     * @covers ::voteOnAttribute
     */
    public function testRemoveDependency()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueA));
        static::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueB));
        static::assertTrue($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $suspended));
        static::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $closed));

        $this->loginAs('dquigley@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueC));
        static::assertTrue($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $createdByDev2));
        static::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $assignedToDev3));

        $this->loginAs('akoepp@example.com');
        static::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueC));
        static::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $createdByDev2));
        static::assertTrue($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $assignedToDev3));
    }
}
