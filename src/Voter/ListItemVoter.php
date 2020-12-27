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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldValue;
use eTraxis\Entity\ListItem;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "ListItem" entities.
 */
class ListItemVoter extends AbstractVoter implements VoterInterface
{
    public const CREATE_ITEM = 'listitem.create';
    public const UPDATE_ITEM = 'listitem.update';
    public const DELETE_ITEM = 'listitem.delete';

    protected array $attributes = [
        self::CREATE_ITEM => Field::class,
        self::UPDATE_ITEM => ListItem::class,
        self::DELETE_ITEM => ListItem::class,
    ];

    private EntityManagerInterface $manager;

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
     * {@inheritDoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // User must be logged in.
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {

            case self::CREATE_ITEM:
                return $this->isCreateGranted($subject, $user);

            case self::UPDATE_ITEM:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_ITEM:
                return $this->isDeleteGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether a new item can be created in the specified field.
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    private function isCreateGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin && $subject->state->template->isLocked && $subject->type === FieldType::LIST;
    }

    /**
     * Whether the specified item can be updated.
     *
     * @param ListItem $subject Subject item.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    private function isUpdateGranted(ListItem $subject, User $user): bool
    {
        return $user->isAdmin && $subject->field->state->template->isLocked;
    }

    /**
     * Whether the specified item can be deleted.
     *
     * @param ListItem $subject Subject item.
     * @param User     $user    Current user.
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    private function isDeleteGranted(ListItem $subject, User $user): bool
    {
        // User must be an admin and template must be locked.
        if (!$user->isAdmin || !$subject->field->state->template->isLocked) {
            return false;
        }

        // Can't delete an item if it was used in at least one issue.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(fv.issue)')
            ->from(FieldValue::class, 'fv')
            ->where('fv.field = :field')
            ->andWhere('fv.value = :value')
            ->setParameter('field', $subject->field->id)
            ->setParameter('value', $subject->id);

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return $result === 0;
    }
}
