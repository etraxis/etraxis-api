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

use Psr\SimpleCache\CacheInterface;
use Sabre\Cache\Memory;

/**
 * PSR-16 cache to store found entities.
 *
 * @method findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
trait CachedRepositoryTrait
{
    private CacheInterface $cache;

    /**
     * {@inheritdoc}
     */
    public function warmup(array $ids): int
    {
        $this->initCache();

        $entities = $this->findBy(['id' => $ids]);

        foreach ($entities as $entity) {
            $this->cache->set("{$entity->id}", $entity);
        }

        return count($entities);
    }

    /**
     * Tries to find an entity by its ID in the following sequence - cache, repository.
     * If the entity was retrieved from the repository, stores it in the cache.
     *
     * @param null|array|int $id
     * @param callable       $callback
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return null|object
     */
    protected function findInCache($id, callable $callback)
    {
        if ($id === null) {
            return null;
        }

        if (is_array($id)) {
            $id = reset($id);
        }

        $this->initCache();

        if ($this->cache->has("{$id}")) {
            return $this->cache->get("{$id}");
        }

        $entity = $callback($id);

        if ($entity !== null) {
            $this->cache->set("{$id}", $entity);
        }

        return $entity;
    }

    /**
     * Deletes specified entity from cache.
     *
     * @param null|int $id
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return bool
     */
    protected function deleteFromCache(?int $id): bool
    {
        $this->initCache();

        return $id !== null
            ? $this->cache->delete("{$id}")
            : false;
    }

    /**
     * Initialises the cache.
     * Should be called first before calling any other function of this trait.
     */
    private function initCache(): void
    {
        if (!isset($this->cache)) {
            $this->cache = new Memory();
        }
    }
}
