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
class Project
{
    /**
     * @API\Property(type="integer", example=123, description="Project ID.")
     */
    public $id;

    /**
     * @API\Property(type="string", example="Project A", description="Project name.")
     */
    public $name;

    /**
     * @API\Property(type="string", example="Our initial startup", description="Optional description.")
     */
    public $description;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the project has been registered.")
     */
    public $created;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the project is suspended.")
     */
    public $suspended;
}
