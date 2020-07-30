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

namespace eTraxis\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Entity\Change;
use eTraxis\Entity\Event;
use eTraxis\Entity\Issue;
use eTraxis\Entity\StateGroupTransition;
use eTraxis\Entity\StateResponsibleGroup;
use eTraxis\Entity\StateRoleTransition;
use eTraxis\Entity\User;

class IssueRepository extends ServiceEntityRepository implements Contracts\IssueRepositoryInterface
{
    use CachedRepositoryTrait;

    private Contracts\ChangeRepositoryInterface      $changeRepository;
    private Contracts\StringValueRepositoryInterface $stringRepository;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ManagerRegistry                          $registry,
        Contracts\ChangeRepositoryInterface      $changeRepository,
        Contracts\StringValueRepositoryInterface $stringRepository
    )
    {
        parent::__construct($registry, Issue::class);

        $this->changeRepository = $changeRepository;
        $this->stringRepository = $stringRepository;
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(Issue $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(Issue $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function refresh(Issue $entity): void
    {
        $this->getEntityManager()->refresh($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->findInCache($id, fn ($id) => parent::find($id));
    }

    /**
     * {@inheritdoc}
     */
    public function getTransitionsByUser(Issue $issue, User $user): array
    {
        // List opened dependencies of the issue.
        $dependencies = array_filter($issue->dependencies, fn (Issue $dependency) => !$dependency->isClosed);

        // List user's roles.
        $roles = [SystemRole::ANYONE];

        if ($issue->author === $user) {
            $roles[] = SystemRole::AUTHOR;
        }

        if ($issue->responsible === $user) {
            $roles[] = SystemRole::RESPONSIBLE;
        }

        // Check whether the user has required permissions by role.
        $query = $this->getEntityManager()->createQueryBuilder();

        $query
            ->select('st')
            ->from(StateRoleTransition::class, 'st')
            ->innerJoin('st.toState', 'toState')
            ->where('st.fromState = :from')
            ->andWhere($query->expr()->in('st.role', ':roles'))
            ->setParameters([
                'from'  => $issue->state,
                'roles' => $roles,
            ]);

        if (count($dependencies) !== 0) {
            $query
                ->andWhere('toState.type != :type')
                ->setParameter('type', StateType::FINAL);
        }

        $statesByRole = array_map(fn (StateRoleTransition $transition) => $transition->toState, $query->getQuery()->getResult());

        // Check whether the user has required permissions by group.
        $query = $this->getEntityManager()->createQueryBuilder();

        $query
            ->select('st')
            ->from(StateGroupTransition::class, 'st')
            ->innerJoin('st.toState', 'toState')
            ->where('st.fromState = :from')
            ->andWhere($query->expr()->in('st.group', ':groups'))
            ->setParameters([
                'from'   => $issue->state,
                'groups' => $user->groups,
            ]);

        if (count($dependencies) !== 0) {
            $query
                ->andWhere('toState.type != :type')
                ->setParameter('type', StateType::FINAL);
        }

        $statesByGroup = array_map(fn (StateGroupTransition $transition) => $transition->toState, $query->getQuery()->getResult());

        $states = array_merge($statesByRole, $statesByGroup);
        $states = array_unique($states);

        return $states;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponsiblesByUser(Issue $issue, User $user, bool $skipCurrent = false): array
    {
        $query = $this->getEntityManager()->createQueryBuilder();

        $query
            ->select('user')
            ->from(User::class, 'user')
            ->from(StateResponsibleGroup::class, 'sr')
            ->innerJoin('user.groupsCollection', 'grp')
            ->where('sr.group = grp')
            ->andWhere('sr.state = :state')
            ->orderBy('user.fullname')
            ->setParameter('state', $issue->state);

        if ($skipCurrent && $issue->responsible !== null) {
            $query
                ->andWhere('user != :responsible')
                ->setParameter('responsible', $issue->responsible);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function changeSubject(Issue $issue, Event $event, string $subject): void
    {
        if ($issue->subject !== $subject) {

            $oldValue = $this->stringRepository->get($issue->subject)->id;
            $newValue = $this->stringRepository->get($subject)->id;

            $change = new Change($event, null, $oldValue, $newValue);

            $issue->subject = $subject;
            $issue->touch();

            $this->changeRepository->persist($change);
            $this->persist($issue);
        }
    }
}
