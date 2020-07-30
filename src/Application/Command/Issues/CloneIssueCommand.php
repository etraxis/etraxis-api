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
 * Clones specified issue.
 *
 * @property int    $issue       ID of the original issue.
 * @property string $subject     New issue subject.
 * @property int    $responsible ID of user to assign new issue to (ignored when not applicable).
 * @property array  $fields      Fields values (keys are field IDs).
 */
class CloneIssueCommand extends AbstractIssueCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public int $issue;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="250")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=250, example="Short bug description", description="Issue subject.")
     */
    public string $subject;
}
