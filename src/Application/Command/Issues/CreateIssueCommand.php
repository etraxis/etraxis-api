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
 * Creates new issue.
 *
 * @property int    $template    ID of the template to use.
 * @property string $subject     Issue subject.
 * @property int    $responsible ID of user to assign new issue to (ignored when not applicable).
 * @property array  $fields      Fields values (keys are field IDs).
 */
class CreateIssueCommand extends AbstractIssueCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", example=123, description="Template ID.")
     */
    public int $template;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="250")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=250, example="Short bug description", description="Issue subject.")
     */
    public string $subject;
}
