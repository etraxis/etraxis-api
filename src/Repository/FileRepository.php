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
use eTraxis\Entity\File;

class FileRepository extends ServiceEntityRepository implements Contracts\FileRepositoryInterface
{
    use CachedRepositoryTrait;

    /**
     * @var string Path to files storage directory.
     */
    private $storage;

    /**
     * {@inheritDoc}
     */
    public function __construct(ManagerRegistry $registry, string $storage)
    {
        parent::__construct($registry, File::class);

        $this->storage = realpath($storage) ?: $storage;
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritDoc}
     */
    public function persist(File $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritDoc}
     */
    public function remove(File $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritDoc}
     */
    public function refresh(File $entity): void
    {
        $this->getEntityManager()->refresh($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * {@inheritDoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->findInCache($id, fn ($id) => parent::find($id));
    }

    /**
     * {@inheritDoc}
     */
    public function getFullPath(File $entity): ?string
    {
        return $this->storage . \DIRECTORY_SEPARATOR . $entity->uuid;
    }
}
