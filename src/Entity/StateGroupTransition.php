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
 * @ORM\Table(name="state_group_transitions")
 * @ORM\Entity
 *
 * @property-read State $fromState State the transition goes from.
 * @property-read State $toState   State the transition goes to.
 * @property-read Group $group     Group.
 */
class StateGroupTransition implements \JsonSerializable
{
    use PropertyTrait;

    // JSON properties.
    public const JSON_STATE = 'state';
    public const JSON_GROUP = 'group';

    /**
     * @var State
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="State", inversedBy="groupTransitionsCollection")
     * @ORM\JoinColumn(name="state_from_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $fromState;

    /**
     * @var State
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumn(name="state_to_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $toState;

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
     * @param State $fromState
     * @param State $toState
     * @param Group $group
     */
    public function __construct(State $fromState, State $toState, Group $group)
    {
        if ($fromState->template !== $toState->template) {
            throw new \UnexpectedValueException('States must belong the same template.');
        }

        if (!$group->isGlobal && $group->project !== $fromState->template->project) {
            throw new \UnexpectedValueException('Unknown group: ' . $group->name);
        }

        $this->fromState = $fromState;
        $this->toState   = $toState;
        $this->group     = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_STATE => $this->toState->id,
            self::JSON_GROUP => $this->group->id,
        ];
    }
}
