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

namespace eTraxis\Application\Query\Issues\Handler;

use Doctrine\ORM\QueryBuilder;
use eTraxis\Application\Query\Collection;
use eTraxis\Application\Query\Issues\GetWatchersQuery;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Repository\Contracts\WatcherRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetWatchersHandler
{
    private $security;
    private $issueRepository;
    private $watcherRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param IssueRepositoryInterface      $issueRepository
     * @param WatcherRepositoryInterface    $watcherRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        IssueRepositoryInterface      $issueRepository,
        WatcherRepositoryInterface    $watcherRepository
    )
    {
        $this->security          = $security;
        $this->issueRepository   = $issueRepository;
        $this->watcherRepository = $watcherRepository;
    }

    /**
     * Query handler.
     *
     * @param GetWatchersQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Collection
     */
    public function __invoke(GetWatchersQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_USER)) {
            throw new AccessDeniedHttpException();
        }

        /** @var \eTraxis\Entity\Issue $issue */
        $issue = $this->issueRepository->find($query->issue);

        if (!$issue) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue)) {
            throw new AccessDeniedHttpException();
        }

        $collection = new Collection();

        $dql = $this->watcherRepository->createQueryBuilder('watcher');

        // Include user.
        $dql->innerJoin('watcher.user', 'user');
        $dql->addSelect('user');

        // Restrict results to the specified issue.
        $dql->where('watcher.issue = :issue');
        $dql->setParameter('issue', $issue);

        // Search.
        $this->querySearch($dql, $query->search);

        // Filter.
        foreach ($query->filter as $property => $value) {
            $this->queryFilter($dql, $property, $value);
        }

        // Total number of entities.
        $total = clone $dql;
        $total->select('COUNT(user.id)');
        $collection->total = (int) $total->getQuery()->getSingleScalarResult();

        // Sorting.
        foreach ($query->sort as $property => $direction) {
            $dql = $this->queryOrder($dql, $property, $direction);
        }

        // Pagination.
        $dql->setFirstResult($query->offset);
        $dql->setMaxResults($query->limit);

        // Execute query.
        $collection->data = $dql->getQuery()->getResult();
        $collection->from = $query->offset;
        $collection->to   = count($collection->data) + $query->offset - 1;

        return $collection;
    }

    /**
     * Alters query in accordance with the specified search.
     *
     * @param QueryBuilder $dql
     * @param string       $search
     *
     * @return QueryBuilder
     */
    private function querySearch(QueryBuilder $dql, ?string $search): QueryBuilder
    {
        if (mb_strlen($search) !== 0) {

            $dql->andWhere($dql->expr()->orX(
                'LOWER(user.email) LIKE :search',
                'LOWER(user.fullname) LIKE :search'
            ));

            $dql->setParameter('search', mb_strtolower("%{$search}%"));
        }

        return $dql;
    }

    /**
     * Alters query to filter by the specified property.
     *
     * @param QueryBuilder $dql
     * @param string       $property
     * @param mixed        $value
     *
     * @return QueryBuilder
     */
    private function queryFilter(QueryBuilder $dql, string $property, $value = null): QueryBuilder
    {
        switch ($property) {

            case User::JSON_EMAIL:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('LOWER(user.email) LIKE LOWER(:email)');
                    $dql->setParameter('email', "%{$value}%");
                }

                break;

            case User::JSON_FULLNAME:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('LOWER(user.fullname) LIKE LOWER(:fullname)');
                    $dql->setParameter('fullname', "%{$value}%");
                }

                break;
        }

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     *
     * @param QueryBuilder $dql
     * @param string       $property
     * @param string       $direction
     *
     * @return QueryBuilder
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $map = [
            User::JSON_EMAIL    => 'user.email',
            User::JSON_FULLNAME => 'user.fullname',
        ];

        if (mb_strtoupper($direction) !== GetWatchersQuery::SORT_DESC) {
            $direction = GetWatchersQuery::SORT_ASC;
        }

        return $dql->addOrderBy($map[$property], $direction);
    }
}
