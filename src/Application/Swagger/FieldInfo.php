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
class FieldInfo
{
    /**
     * @API\Property(type="integer", example=123, description="Field ID.")
     */
    public int $id;

    /**
     * @API\Property(type="string", example="Severity", description="Field name.")
     */
    public string $name;

    /**
     * @API\Property(type="string", enum={
     *     "checkbox",
     *     "date",
     *     "decimal",
     *     "duration",
     *     "issue",
     *     "list",
     *     "number",
     *     "string",
     *     "text"
     * }, example="list", description="Field type.")
     */
    public string $type;

    /**
     * @API\Property(type="string", example="Error severity", description="Optional description.")
     */
    public ?string $description;

    /**
     * @API\Property(type="integer", example=1, description="Ordinal number of the field among other fields of the same state.")
     */
    public int $position;

    /**
     * @API\Property(type="boolean", example=true, description="Whether the field is required.")
     */
    public bool $required;
}
