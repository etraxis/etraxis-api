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
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldValue;
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Voter for "Field" entities.
 */
class FieldVoter extends AbstractVoter
{
    public const CREATE_FIELD    = 'field.create';
    public const UPDATE_FIELD    = 'field.update';
    public const REMOVE_FIELD    = 'field.remove';
    public const DELETE_FIELD    = 'field.delete';
    public const GET_PERMISSIONS = 'field.permissions.get';
    public const SET_PERMISSIONS = 'field.permissions.set';

    protected $attributes = [
        self::CREATE_FIELD    => State::class,
        self::UPDATE_FIELD    => Field::class,
        self::REMOVE_FIELD    => Field::class,
        self::DELETE_FIELD    => Field::class,
        self::GET_PERMISSIONS => Field::class,
        self::SET_PERMISSIONS => Field::class,
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

            case self::CREATE_FIELD:
                return $this->isCreateGranted($subject, $user);

            case self::UPDATE_FIELD:
                return $this->isUpdateGranted($subject, $user);

            case self::REMOVE_FIELD:
                return $this->isRemoveGranted($subject, $user);

            case self::DELETE_FIELD:
                return $this->isDeleteGranted($subject, $user);

            case self::GET_PERMISSIONS:
                return $this->isGetPermissionsGranted($subject, $user);

            case self::SET_PERMISSIONS:
                return $this->isSetPermissionsGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether a new field can be created in the specified state.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isCreateGranted(State $subject, User $user): bool
    {
        return $user->isAdmin && $subject->template->isLocked;
    }

    /**
     * Whether the specified field can be updated.
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isUpdateGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin && $subject->state->template->isLocked;
    }

    /**
     * Whether the specified field can be removed (soft-deleted).
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isRemoveGranted(Field $subject, User $user): bool
    {
        // User must be an admin and template must be locked.
        return $user->isAdmin && $subject->state->template->isLocked;
    }

    /**
     * Whether the specified field can be deleted.
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    private function isDeleteGranted(Field $subject, User $user): bool
    {
        // It must be allowed to soft-delete the field.
        if (!$this->isRemoveGranted($subject, $user)) {
            return false;
        }

        // Can't delete a field if it was used in at least one issue.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(fv.issue)')
            ->from(FieldValue::class, 'fv')
            ->where('fv.field = :field')
            ->setParameter('field', $subject->id);

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return $result === 0;
    }

    /**
     * Whether permissions of the specified field can be retrieved.
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isGetPermissionsGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether permissions of the specified field can be changed.
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isSetPermissionsGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin && $subject->state->template->isLocked;
    }
}
