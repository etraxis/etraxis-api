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

namespace eTraxis\Repository\Contracts;

/**
 * Interface for repository with entities cache.
 */
interface CachedRepositoryInterface
{
    /**
     * Retrieves from the repository all entities specified by their IDs,
     * and stores them in the cache.
     *
     * @param array $ids
     *
     * @return int Number of entities pushed to the cache.
     */
    public function warmup(array $ids): int;
}
