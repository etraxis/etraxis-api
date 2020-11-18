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
use eTraxis\Entity\Template;

class TemplateRepository extends ServiceEntityRepository implements Contracts\TemplateRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(Template $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(Template $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function refresh(Template $entity): void
    {
        $this->getEntityManager()->refresh($entity);
    }
}
