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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use eTraxis\Application\Dictionary\StateResponsible;
use eTraxis\Application\Dictionary\StateType;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * State.
 *
 * @ORM\Table(
 *     name="states",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"template_id", "name"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\StateRepository")
 * @Assert\UniqueEntity(fields={"template", "name"}, message="state.conflict.name")
 *
 * @property-read int                     $id                Unique ID.
 * @property-read Template                $template          Template of the state.
 * @property      string                  $name              Name of the state.
 * @property-read string                  $type              Type of the state (see the "StateType" dictionary).
 * @property      string                  $responsible       Type of responsibility management (see the "StateResponsible" dictionary).
 * @property      null|State              $nextState         Next state by default (optional).
 * @property-read bool                    $isFinal           Whether the state is final.
 * @property-read Field[]                 $fields            List of state fields.
 * @property-read StateRoleTransition[]   $roleTransitions   List of state role transitions.
 * @property-read StateGroupTransition[]  $groupTransitions  List of state group transitions.
 * @property-read StateResponsibleGroup[] $responsibleGroups List of responsible groups.
 */
class State
{
    use PropertyTrait;

    // Constraints.
    public const MAX_NAME = 50;

    // JSON properties.
    public const JSON_ID          = 'id';
    public const JSON_PROJECT     = 'project';
    public const JSON_TEMPLATE    = 'template';
    public const JSON_NAME        = 'name';
    public const JSON_TYPE        = 'type';
    public const JSON_RESPONSIBLE = 'responsible';
    public const JSON_NEXT        = 'next';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Template
     *
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="statesCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="template_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $template;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=12)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="responsible", type="string", length=10)
     */
    protected $responsible;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumn(name="next_state_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $nextState;

    /**
     * @var ArrayCollection|Field[]
     *
     * @ORM\OneToMany(targetEntity="Field", mappedBy="state")
     * @ORM\OrderBy({"position": "ASC"})
     */
    protected $fieldsCollection;

    /**
     * @var ArrayCollection|StateRoleTransition[]
     *
     * @ORM\OneToMany(targetEntity="StateRoleTransition", mappedBy="fromState")
     */
    protected $roleTransitionsCollection;

    /**
     * @var ArrayCollection|StateGroupTransition[]
     *
     * @ORM\OneToMany(targetEntity="StateGroupTransition", mappedBy="fromState")
     */
    protected $groupTransitionsCollection;

    /**
     * @var ArrayCollection|StateResponsibleGroup[]
     *
     * @ORM\OneToMany(targetEntity="StateResponsibleGroup", mappedBy="state")
     */
    protected $responsibleGroupsCollection;

    /**
     * Creates new state in the specified template.
     *
     * @param Template $template
     * @param string   $type
     */
    public function __construct(Template $template, string $type)
    {
        if (!StateType::has($type)) {
            throw new \UnexpectedValueException('Unknown state type: ' . $type);
        }

        $this->template    = $template;
        $this->type        = $type;
        $this->responsible = StateResponsible::REMOVE;

        $this->fieldsCollection            = new ArrayCollection();
        $this->roleTransitionsCollection   = new ArrayCollection();
        $this->groupTransitionsCollection  = new ArrayCollection();
        $this->responsibleGroupsCollection = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'responsible' => function (): string {
                return $this->type === StateType::FINAL ? StateResponsible::REMOVE : $this->responsible;
            },

            'nextState' => function (): ?self {
                return $this->type === StateType::FINAL ? null : $this->nextState;
            },

            'isFinal' => function (): bool {
                return $this->type === StateType::FINAL;
            },

            'fields' => function (): array {
                return array_values(array_filter($this->fieldsCollection->getValues(), function (Field $field) {
                    return !$field->isRemoved;
                }));
            },

            'roleTransitions' => function (): array {
                return $this->roleTransitionsCollection->getValues();
            },

            'groupTransitions' => function (): array {
                return $this->groupTransitionsCollection->getValues();
            },

            'responsibleGroups' => function (): array {
                return $this->responsibleGroupsCollection->getValues();
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setters(): array
    {
        return [

            'responsible' => function (string $value): void {
                if (StateResponsible::has($value)) {
                    $this->responsible = $value;
                }
                else {
                    throw new \UnexpectedValueException('Unknown responsibility type: ' . $value);
                }
            },

            'nextState' => function (?self $value): void {
                if ($value === null || $value->template === $this->template) {
                    $this->nextState = $value;
                }
                else {
                    throw new \UnexpectedValueException('Unknown state: ' . $value->name);
                }
            },
        ];
    }
}
