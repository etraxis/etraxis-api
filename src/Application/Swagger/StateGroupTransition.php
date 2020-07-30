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

namespace eTraxis\Application\Swagger;

use Swagger\Annotations as API;

/**
 * Descriptive class for API annotations.
 */
class StateGroupTransition
{
    /**
     * @API\Property(type="integer", example=123, description="State ID.")
     */
    public int $state;

    /**
     * @API\Property(type="integer", example=123, description="Group ID.")
     */
    public int $group;
}
