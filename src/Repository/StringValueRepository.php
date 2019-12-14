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
use eTraxis\Entity\StringValue;

class StringValueRepository extends ServiceEntityRepository implements Contracts\StringValueRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StringValue::class);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(StringValue $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(StringValue $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function refresh(StringValue $entity): void
    {
        $this->getEntityManager()->refresh($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $value): StringValue
    {
        /** @var null|StringValue $entity */
        $entity = $this->findOneBy([
            'token' => md5($value),
        ]);

        // If value doesn't exist yet, create it.
        if ($entity === null) {

            $entity = new StringValue($value);

            $this->getEntityManager()->persist($entity);
            $this->getEntityManager()->flush();
        }

        return $entity;
    }
}
