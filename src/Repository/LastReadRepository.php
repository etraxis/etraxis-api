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
use Doctrine\Common\Persistence\ManagerRegistry;
use eTraxis\Entity\Issue;
use eTraxis\Entity\LastRead;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LastReadRepository extends ServiceEntityRepository implements Contracts\LastReadRepositoryInterface
{
    use CachedRepositoryTrait;

    private $tokens;

    /**
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry, TokenStorageInterface $tokens)
    {
        parent::__construct($registry, LastRead::class);

        $this->tokens = $tokens;
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(LastRead $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->deleteFromCache($entity->issue->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(LastRead $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->deleteFromCache($entity->issue->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function refresh(LastRead $entity): void
    {
        $this->getEntityManager()->refresh($entity);
        $this->deleteFromCache($entity->issue->id);
    }

    /**
     * {@inheritdoc}
     */
    public function warmup(array $ids): int
    {
        $this->initCache();

        /** @var User $user */
        $user = $this->tokens->getToken()->getUser();

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
     * {@inheritdoc}
     */
    public function findLastRead(Issue $issue): ?LastRead
    {
        /** @var User $user */
        $user = $this->tokens->getToken()->getUser();

        /** @var null|LastRead $entity */
        $entity = $this->findInCache($issue->id, function ($id) use ($user) {
            return $this->findOneBy([
                'issue' => $id,
                'user'  => $user,
            ]);
        });

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsRead(Issue $issue): void
    {
        /** @var User $user */
        $user = $this->tokens->getToken()->getUser();

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
