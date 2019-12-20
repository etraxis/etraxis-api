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
 * Adds new comment to specified issue.
 *
 * @property int    $issue   Issue ID.
 * @property string $body    Comment body.
 * @property bool   $private Whether the comment is private.
 */
class AddCommentCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $issue;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="10000")
     */
    public $body;

    /**
     * @Assert\NotNull
     */
    public $private;
}
