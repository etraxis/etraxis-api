<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Entity;

use Doctrine\ORM\Mapping as ORM;
use eTraxis\Application\Dictionary\SystemRole;
use Webinarium\PropertyTrait;

/**
 * State transition for system role.
 *
 * @ORM\Table(name="state_role_transitions")
 * @ORM\Entity
 *
 * @property-read State  $fromState State the transition goes from.
 * @property-read State  $toState   State the transition goes to.
 * @property-read string $role      System role.
 */
class StateRoleTransition implements \JsonSerializable
{
    use PropertyTrait;

    // JSON properties.
    public const JSON_STATE = 'state';
    public const JSON_ROLE  = 'role';

    /**
     * @var State
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="State", inversedBy="roleTransitionsCollection")
     * @ORM\JoinColumn(name="state_from_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected State $fromState;

    /**
     * @var State
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumn(name="state_to_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected State $toState;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="role", type="string", length=20)
     */
    protected string $role;

    /**
     * Constructor.
     *
     * @param State  $fromState
     * @param State  $toState
     * @param string $role
     */
    public function __construct(State $fromState, State $toState, string $role)
    {
        if ($fromState->template !== $toState->template) {
            throw new \UnexpectedValueException('States must belong the same template.');
        }

        if (!SystemRole::has($role)) {
            throw new \UnexpectedValueException('Unknown system role: ' . $role);
        }

        $this->fromState = $fromState;
        $this->toState   = $toState;
        $this->role      = $role;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_STATE => $this->toState->id,
            self::JSON_ROLE  => $this->role,
        ];
    }
}
