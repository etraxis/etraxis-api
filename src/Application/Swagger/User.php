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
class User
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
     * @API\Property(type="string", example="very lovely daughter", description="Optional description.")
     */
    public $description;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the user has administrator privileges.")
     */
    public $admin;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the user's account is disabled.")
     */
    public $disabled;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the user's account is locked.")
     */
    public $locked;

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
     * @API\Property(type="boolean", example=true, description="Whether the user has light theme mode set by default.")
     */
    public $light_mode;

    /**
     * @API\Property(type="string", example="Pacific/Auckland", description="Timezone (IANA database value).")
     */
    public $timezone;

    /**
     * @API\Property(type="array", description="List of HATEOAS links.", @API\Items(
     *     type="object",
     *     properties={
     *         @API\Property(property="rel",  type="string", example="self", description="API link related to the user."),
     *         @API\Property(property="href", type="string", example="https://example.com/api/users/123", description="Absolute URL of the link."),
     *         @API\Property(property="type", type="string", example="GET", description="HTTP method of the link.")
     *     }
     * ))
     */
    public $links;
}
