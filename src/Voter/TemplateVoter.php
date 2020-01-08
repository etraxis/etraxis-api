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
use eTraxis\Entity\Template;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Voter for "Template" entities.
 */
class TemplateVoter extends AbstractVoter
{
    public const CREATE_TEMPLATE = 'template.create';
    public const UPDATE_TEMPLATE = 'template.update';
    public const DELETE_TEMPLATE = 'template.delete';
    public const LOCK_TEMPLATE   = 'template.lock';
    public const UNLOCK_TEMPLATE = 'template.unlock';
    public const GET_PERMISSIONS = 'template.permissions.get';
    public const SET_PERMISSIONS = 'template.permissions.set';

    protected $attributes = [
        self::CREATE_TEMPLATE => Project::class,
        self::UPDATE_TEMPLATE => Template::class,
        self::DELETE_TEMPLATE => Template::class,
        self::LOCK_TEMPLATE   => Template::class,
        self::UNLOCK_TEMPLATE => Template::class,
        self::GET_PERMISSIONS => Template::class,
        self::SET_PERMISSIONS => Template::class,
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

            case self::CREATE_TEMPLATE:
                return $this->isCreateGranted($subject, $user);

            case self::UPDATE_TEMPLATE:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_TEMPLATE:
                return $this->isDeleteGranted($subject, $user);

            case self::LOCK_TEMPLATE:
                return $this->isLockGranted($subject, $user);

            case self::UNLOCK_TEMPLATE:
                return $this->isUnlockGranted($subject, $user);

            case self::GET_PERMISSIONS:
                return $this->isGetPermissionsGranted($subject, $user);

            case self::SET_PERMISSIONS:
                return $this->isSetPermissionsGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether a new template can be created in the specified project.
     *
     * @param Project $project Subject project.
     * @param User    $user    Current user.
     *
     * @return bool
     */
    private function isCreateGranted(Project $project, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified template can be updated.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    private function isUpdateGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified template can be deleted.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    private function isDeleteGranted(Template $subject, User $user): bool
    {
        // User must be an admin.
        if (!$user->isAdmin) {
            return false;
        }

        // Can't delete a template if at least one issue is created using it.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(issue.id)')
            ->from(Issue::class, 'issue')
            ->innerJoin('issue.state', 'state')
            ->where('state.template = :template')
            ->setParameter('template', $subject->id);

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return $result === 0;
    }

    /**
     * Whether the specified template can be locked.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    private function isLockGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin && !$subject->isLocked;
    }

    /**
     * Whether the specified template can be unlocked.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    private function isUnlockGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin && $subject->isLocked;
    }

    /**
     * Whether permissions of the specified template can be retrieved.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    private function isGetPermissionsGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether permissions of the specified template can be changed.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    private function isSetPermissionsGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin;
    }
}
