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

namespace eTraxis\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use eTraxis\Entity\Issue;
use eTraxis\Entity\LastRead;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LastReadRepository extends ServiceEntityRepository implements Contracts\LastReadRepositoryInterface
{
    use CachedRepositoryTrait;

    private $tokenStorage;

    /**
     * {@inheritDoc}
     */
    public function __construct(ManagerRegistry $registry, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($registry, LastRead::class);

        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritDoc}
     */
    public function persist(LastRead $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->deleteFromCache($entity->issue->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritDoc}
     */
    public function remove(LastRead $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->deleteFromCache($entity->issue->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritDoc}
     */
    public function refresh(LastRead $entity): void
    {
        $this->getEntityManager()->refresh($entity);
        $this->deleteFromCache($entity->issue->id);
    }

    /**
     * {@inheritDoc}
     */
    public function warmup(array $ids): int
    {
        $this->initCache();

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var LastRead[] $entities */
        $entities = $this->findBy([
            'issue' => $ids,
            'user'  => $user,
        ]);

        // Remember found entities.
        foreach ($entities as $entity) {
            $this->cache->set("{$entity->issue->id}", $entity);
        }

        $result = count($entities);

        // Remember `null` for not found entities.
        foreach ($ids as $id) {
            if (!$this->cache->has("{$id}")) {
                $this->cache->set("{$id}", null);
                $result++;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function findLastRead(Issue $issue): ?LastRead
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|LastRead $entity */
        $entity = $this->findInCache($issue->id, fn ($id) => $this->findOneBy([
            'issue' => $id,
            'user'  => $user,
        ]));

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function markAsRead(Issue $issue): void
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|LastRead $entity */
        $entity = $this->findLastRead($issue);

        // If value doesn't exist yet, create it.
        if ($entity === null) {
            $entity = new LastRead($issue, $user);
        }
        else {
            $entity->touch();
        }

        $this->persist($entity);

        $this->getEntityManager()->flush();
    }
}
