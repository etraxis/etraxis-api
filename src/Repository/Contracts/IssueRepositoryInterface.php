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

namespace eTraxis\Repository\Contracts;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;
use eTraxis\Entity\Event;
use eTraxis\Entity\Issue;
use eTraxis\Entity\User;

/**
 * Interface to the 'Issue' entities repository.
 */
interface IssueRepositoryInterface extends CachedRepositoryInterface, ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     *
     * @param Issue $entity
     */
    public function persist(Issue $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     *
     * @param Issue $entity
     */
    public function remove(Issue $entity): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::refresh()
     *
     * @param Issue $entity
     */
    public function refresh(Issue $entity): void;

    /**
     * @see \Doctrine\ORM\EntityRepository::createQueryBuilder()
     *
     * @param string      $alias
     * @param null|string $indexBy
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createQueryBuilder(string $alias, ?string $indexBy = null);

    /**
     * Returns list of all states which the issue can be moved to by specified user.
     *
     * @param Issue $issue Issue which current state is to be changed.
     * @param User  $user  User who's changing current state of the issue.
     *
     * @return \eTraxis\Entity\State[]
     */
    public function getTransitionsByUser(Issue $issue, User $user): array;

    /**
     * Returns list of all possible assignees available to specified user.
     *
     * @param Issue $issue       Issue which is to be (re)assigned.
     * @param User  $user        User who's (re)assigning the issue.
     * @param bool  $skipCurrent Whether to skip current responsible.
     *
     * @return User[]
     */
    public function getResponsiblesByUser(Issue $issue, User $user, bool $skipCurrent = false): array;

    /**
     * Sets new subject of the specified issue.
     *
     * @param Issue  $issue   Issie whose subject is being set.
     * @param Event  $event   Event related to this change.
     * @param string $subject New subject.
     */
    public function changeSubject(Issue $issue, Event $event, string $subject): void;
}
