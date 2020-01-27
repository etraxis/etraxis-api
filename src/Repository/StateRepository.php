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
use eTraxis\Entity\State;

class StateRepository extends ServiceEntityRepository implements Contracts\StateRepositoryInterface
{
    use CachedRepositoryTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, State::class);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(State $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(State $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function refresh(State $entity): void
    {
        $this->getEntityManager()->refresh($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->findInCache($id, function ($id) {
            return parent::find($id);
        });
    }
}
