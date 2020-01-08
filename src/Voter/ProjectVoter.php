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

namespace eTraxis\Voter;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Entity\Issue;
use eTraxis\Entity\Project;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Voter for "Project" entities.
 */
class ProjectVoter extends AbstractVoter
{
    public const CREATE_PROJECT  = 'project.create';
    public const UPDATE_PROJECT  = 'project.update';
    public const DELETE_PROJECT  = 'project.delete';
    public const SUSPEND_PROJECT = 'project.suspend';
    public const RESUME_PROJECT  = 'project.resume';

    protected $attributes = [
        self::CREATE_PROJECT  => null,
        self::UPDATE_PROJECT  => Project::class,
        self::DELETE_PROJECT  => Project::class,
        self::SUSPEND_PROJECT => Project::class,
        self::RESUME_PROJECT  => Project::class,
    ];

    private $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // User must be logged in.
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {

            case self::CREATE_PROJECT:
                return $this->isCreateGranted($user);

            case self::UPDATE_PROJECT:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_PROJECT:
                return $this->isDeleteGranted($subject, $user);

            case self::SUSPEND_PROJECT:
                return $this->isSuspendGranted($subject, $user);

            case self::RESUME_PROJECT:
                return $this->isResumeGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether the current user can create a new project.
     *
     * @param User $user Current user.
     *
     * @return bool
     */
    private function isCreateGranted(User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified project can be updated.
     *
     * @param Project $subject Subject project.
     * @param User    $user    Current user.
     *
     * @return bool
     */
    private function isUpdateGranted(Project $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified project can be deleted.
     *
     * @param Project $subject Subject project.
     * @param User    $user    Current user.
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    private function isDeleteGranted(Project $subject, User $user): bool
    {
        // User must be an admin.
        if (!$user->isAdmin) {
            return false;
        }

        // Can't delete a project if there is at least one issue there.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(issue.id)')
            ->from(Issue::class, 'issue')
            ->innerJoin('issue.state', 'state')
            ->innerJoin('state.template', 'template')
            ->where('template.project = :project')
            ->setParameter('project', $subject->id);

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return $result === 0;
    }

    /**
     * Whether the specified project can be suspended.
     *
     * @param Project $subject Subject project.
     * @param User    $user    Current user.
     *
     * @return bool
     */
    private function isSuspendGranted(Project $subject, User $user): bool
    {
        return $user->isAdmin && !$subject->isSuspended;
    }

    /**
     * Whether the specified project can be resumed.
     *
     * @param Project $subject Subject project.
     * @param User    $user    Current user.
     *
     * @return bool
     */
    private function isResumeGranted(Project $subject, User $user): bool
    {
        return $user->isAdmin && $subject->isSuspended;
    }
}
