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
class Profile
{
    /**
     * @API\Property(type="integer", example=123, description="User ID.")
     */
    public $id;

    /**
     * @API\Property(type="string", example="anna@example.com", description="Email address (RFC 5322).")
     */
    public $email;

    /**
     * @API\Property(type="string", example="Anna Rodygina", description="Full name.")
     */
    public $fullname;

    /**
     * @API\Property(type="string", enum={"etraxis", "ldap", "google", "github", "bitbucket"}, example="etraxis", description="Account provider.")
     */
    public $provider;

    /**
     * @API\Property(type="string", example="en_NZ", description="Locale (ISO 639-1 / ISO 3166-1).")
     */
    public $locale;

    /**
     * @API\Property(type="string", enum={"azure", "emerald", "mars"}, example="azure", description="Theme.")
     */
    public $theme;

    /**
     * @API\Property(type="string", example="Pacific/Auckland", description="Timezone (IANA database value).")
     */
    public $timezone;
}
