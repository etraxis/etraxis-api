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
 * State transition for group.
 *
 * @ORM\Table(name="state_responsible_groups")
 * @ORM\Entity
 *
 * @property-read State $state State.
 * @property-read Group $group Group.
 */
class StateResponsibleGroup
{
    use PropertyTrait;

    /**
     * @var State
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="State", inversedBy="responsibleGroupsCollection")
     * @ORM\JoinColumn(name="state_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $state;

    /**
     * @var Group
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\Group")
     * @ORM\JoinColumn(name="group_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $group;

    /**
     * Constructor.
     *
     * @param State $state
     * @param Group $group
     */
    public function __construct(State $state, Group $group)
    {
        if (!$group->isGlobal && $group->project !== $state->template->project) {
            throw new \UnexpectedValueException('Unknown group: ' . $group->name);
        }

        $this->state = $state;
        $this->group = $group;
    }
}
