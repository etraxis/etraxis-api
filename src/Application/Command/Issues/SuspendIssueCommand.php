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
 * Suspends specified issue.
 *
 * @property int    $issue Issue ID.
 * @property string $date  The issue is being suspended until midnight of this date (YYYY-MM-DD).
 */
class SuspendIssueCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public ?int $issue;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d{4}\-[0-1]\d\-[0-3]\d$/")
     *
     * @Groups("api")
     * @API\Property(type="string", format="YYYY-MM-DD", example="2018-09-25", description="Date when the issue will be automatically resumed.")
     */
    public ?string $date;
}
