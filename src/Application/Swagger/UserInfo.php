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

namespace eTraxis\Application\Swagger;

use Swagger\Annotations as API;

/**
 * Descriptive class for API annotations.
 */
class UserInfo
{
    /**
     * @API\Property(type="integer", example=123, description="User ID.")
     */
    public int $id;

    /**
     * @API\Property(type="string", example="anna@example.com", description="Email address (RFC 5322).")
     */
    public string $email;

    /**
     * @API\Property(type="string", example="Anna Rodygina", description="Full name.")
     */
    public string $fullname;
}
