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

namespace eTraxis\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webinarium\PropertyTrait;

/**
 * Issue last read.
 *
 * @ORM\Table(name="last_reads")
 * @ORM\Entity(repositoryClass="eTraxis\Repository\LastReadRepository")
 *
 * @property-read Issue $issue  Issue.
 * @property-read User  $user   User.
 * @property-read int   $readAt Unix Epoch timestamp when the issue has been viewed by user last time.
 */
class LastRead
{
    use PropertyTrait;

    /**
     * @var Issue
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Issue")
     * @ORM\JoinColumn(name="issue_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $issue;

    /**
     * @var User
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\User")
     * @ORM\JoinColumn(name="user_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="read_at", type="integer")
     */
    protected $readAt;

    /**
     * Creates new read.
     *
     * @param Issue $issue
     * @param User  $user
     */
    public function __construct(Issue $issue, User $user)
    {
        $this->issue = $issue;
        $this->user  = $user;

        $this->readAt = time();
    }

    /**
     * Updates the timestamp of when the issue has been read.
     */
    public function touch(): void
    {
        $this->readAt = time();
    }
}
