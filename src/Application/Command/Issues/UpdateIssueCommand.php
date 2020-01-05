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

namespace eTraxis\Application\Command\Issues;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified issue.
 *
 * @property int    $issue   Issue ID.
 * @property string $subject Issue subject.
 * @property array  $fields  Fields values (keys are field IDs).
 */
class UpdateIssueCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $issue;

    /**
     * @Assert\Length(max="250")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=250, example="Short bug description", description="Issue subject.")
     */
    public $subject;

    /**
     * All the constraints are configured at run-time.
     *
     * @Groups("api")
     * @API\Property(type="object", description="Fields values (keys are field IDs).")
     */
    public $fields = [];
}
