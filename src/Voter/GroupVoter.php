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

use eTraxis\Entity\Group;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "Group" entities.
 */
class GroupVoter extends AbstractVoter implements VoterInterface
{
    public const CREATE_GROUP      = 'group.create';
    public const UPDATE_GROUP      = 'group.update';
    public const DELETE_GROUP      = 'group.delete';
    public const MANAGE_MEMBERSHIP = 'group.membership';

    protected $attributes = [
        self::CREATE_GROUP      => null,
        self::UPDATE_GROUP      => Group::class,
        self::DELETE_GROUP      => Group::class,
        self::MANAGE_MEMBERSHIP => Group::class,
    ];

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

            case self::CREATE_GROUP:
                return $this->isCreateGranted($user);

            case self::UPDATE_GROUP:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_GROUP:
                return $this->isDeleteGranted($subject, $user);

            case self::MANAGE_MEMBERSHIP:
                return $this->isManageMembershipGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether the current user can create a new group.
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
     * Whether the specified group can be updated.
     *
     * @param Group $subject Subject group.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isUpdateGranted(Group $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified group can be deleted.
     *
     * @param Group $subject Subject group.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isDeleteGranted(Group $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether list of members of the specified group can be managed.
     *
     * @param Group $subject Subject group.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isManageMembershipGranted(Group $subject, User $user): bool
    {
        return $user->isAdmin;
    }
}
