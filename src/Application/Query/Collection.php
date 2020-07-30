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

namespace eTraxis\Application\Query;

/**
 * A collection of entities.
 */
class Collection
{
    /**
     * @var int Zero-based index of the first returned entity.
     */
    public int $from = 0;

    /**
     * @var int Zero-based index of the last returned entity.
     */
    public int $to = 0;

    /**
     * @var int Total number of all entities.
     */
    public int $total = 0;

    /**
     * @var array Entities.
     */
    public array $data = [];
}
