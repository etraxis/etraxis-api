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

namespace eTraxis\Application\Query\Users\Handler;

use Doctrine\ORM\QueryBuilder;
use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\Application\Query\Collection;
use eTraxis\Application\Query\Users\GetUsersQuery;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetUsersHandler
{
    private $security;
    private $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param UserRepositoryInterface       $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, UserRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Query handler.
     *
     * @param GetUsersQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Collection
     */
    public function __invoke(GetUsersQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException();
        }

        $collection = new Collection();

        $dql = $this->repository->createQueryBuilder('user');

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
                'LOWER(user.fullname) LIKE :search',
                'LOWER(user.description) LIKE :search',
                'LOWER(user.account.provider) LIKE :search'
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

            case User::JSON_DESCRIPTION:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('LOWER(user.description) LIKE LOWER(:description)');
                    $dql->setParameter('description', "%{$value}%");
                }

                break;

            case User::JSON_ADMIN:

                $dql->andWhere('user.role = :role');
                $dql->setParameter('role', $value ? User::ROLE_ADMIN : User::ROLE_USER);

                break;

            case User::JSON_DISABLED:

                $dql->andWhere('user.isEnabled = :enabled');
                $dql->setParameter('enabled', (bool) !$value);

                break;

            case User::JSON_LOCKED:

                if ($value) {
                    $dql->andWhere($dql->expr()->orX(
                        'user.lockedUntil = 0',             // a) the user is locked for indefinite time
                        'user.lockedUntil > :now'           // b) time, the user is locked until, is still in future
                    ));
                }
                else {
                    $dql->andWhere($dql->expr()->orX(
                        'user.lockedUntil IS NULL',         // a) the user was never locked
                        $dql->expr()->andX(
                            'user.lockedUntil != 0',        // b) the user is not locked for indefinite time
                            'user.lockedUntil <= :now'      //    and this time is already in past
                        )
                    ));
                }

                $dql->setParameter('now', time());

                break;

            case User::JSON_PROVIDER:

                if (AccountProvider::has($value)) {
                    $dql->andWhere('LOWER(user.account.provider) = LOWER(:provider)');
                    $dql->setParameter('provider', $value);
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
            User::JSON_ID          => 'user.id',
            User::JSON_EMAIL       => 'user.email',
            User::JSON_FULLNAME    => 'user.fullname',
            User::JSON_DESCRIPTION => 'user.description',
            User::JSON_ADMIN       => 'user.role',
            User::JSON_PROVIDER    => 'user.account.provider',
        ];

        if (mb_strtoupper($direction) !== GetUsersQuery::SORT_DESC) {
            $direction = GetUsersQuery::SORT_ASC;
        }

        return $dql->addOrderBy($map[$property], $direction);
    }
}
