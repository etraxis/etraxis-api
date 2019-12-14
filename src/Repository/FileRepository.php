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
use eTraxis\Entity\File;

class FileRepository extends ServiceEntityRepository implements Contracts\FileRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(File $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(File $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function refresh(File $entity): void
    {
        $this->getEntityManager()->refresh($entity);
    }
}
