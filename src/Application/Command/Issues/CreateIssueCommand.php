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
     */
    public $template;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="250")
     */
    public $subject;
}
