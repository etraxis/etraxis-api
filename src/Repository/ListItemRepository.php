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
use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;

class ListItemRepository extends ServiceEntityRepository implements Contracts\ListItemRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListItem::class);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(ListItem $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(ListItem $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function refresh(ListItem $entity): void
    {
        $this->getEntityManager()->refresh($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllByField(Field $field): array
    {
        return $this->findBy([
            'field' => $field,
        ], [
            'value' => 'ASC',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByValue(Field $field, int $value): ?ListItem
    {
        /** @var ListItem $entity */
        $entity = $this->findOneBy([
            'field' => $field,
            'value' => $value,
        ]);

        return $entity;
    }
}
