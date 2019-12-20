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
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Dictionary\StateResponsible;
use eTraxis\Entity\Event;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Voter for "State" entities.
 */
class StateVoter extends AbstractVoter
{
    public const CREATE_STATE              = 'state.create';
    public const UPDATE_STATE              = 'state.update';
    public const DELETE_STATE              = 'state.delete';
    public const SET_INITIAL               = 'state.set_initial';
    public const MANAGE_TRANSITIONS        = 'state.transitions';
    public const MANAGE_RESPONSIBLE_GROUPS = 'state.responsible_groups';

    protected $attributes = [
        self::CREATE_STATE              => Template::class,
        self::UPDATE_STATE              => State::class,
        self::DELETE_STATE              => State::class,
        self::SET_INITIAL               => State::class,
        self::MANAGE_TRANSITIONS        => State::class,
        self::MANAGE_RESPONSIBLE_GROUPS => State::class,
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

            case self::CREATE_STATE:
                return $this->isCreateGranted($subject, $user);

            case self::UPDATE_STATE:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_STATE:
                return $this->isDeleteGranted($subject, $user);

            case self::SET_INITIAL:
                return $this->isSetInitialGranted($subject, $user);

            case self::MANAGE_TRANSITIONS:
                return $this->isManageTransitionsGranted($subject, $user);

            case self::MANAGE_RESPONSIBLE_GROUPS:
                return $this->isManageResponsibleGroupsGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether a new state can be created in the specified template.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    private function isCreateGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin && $subject->isLocked;
    }

    /**
     * Whether the specified state can be updated.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isUpdateGranted(State $subject, User $user): bool
    {
        return $user->isAdmin && $subject->template->isLocked;
    }

    /**
     * Whether the specified state can be deleted.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    private function isDeleteGranted(State $subject, User $user): bool
    {
        // User must be an admin and template must be locked.
        if (!$user->isAdmin || !$subject->template->isLocked) {
            return false;
        }

        // Can't delete a state if it was used in at least one issue.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(event.id)')
            ->from(Event::class, 'event')
            ->where($query->expr()->in('event.type', ':types'))
            ->andWhere('event.parameter = :state')
            ->setParameter('state', $subject->id)
            ->setParameter('types', [
                EventType::ISSUE_CREATED,
                EventType::ISSUE_REOPENED,
                EventType::ISSUE_CLOSED,
                EventType::STATE_CHANGED,
            ]);

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return $result === 0;
    }

    /**
     * Whether the specified state can be set as initial one.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isSetInitialGranted(State $subject, User $user): bool
    {
        return $user->isAdmin && $subject->template->isLocked;
    }

    /**
     * Whether transitions of the specified state can be changed.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isManageTransitionsGranted(State $subject, User $user): bool
    {
        return $user->isAdmin && $subject->template->isLocked;
    }

    /**
     * Whether responsible groups of the specified state can be changed.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isManageResponsibleGroupsGranted(State $subject, User $user): bool
    {
        return $user->isAdmin && $subject->template->isLocked && $subject->responsible === StateResponsible::ASSIGN;
    }
}
