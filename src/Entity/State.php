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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    protected int $id;

    /**
     * @var Template
     *
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="statesCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="template_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected Template $template;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected string $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=12)
     */
    protected string $type;

    /**
     * @var string
     *
     * @ORM\Column(name="responsible", type="string", length=10)
     */
    protected string $responsible;

    /**
     * @var null|State
     *
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumn(name="next_state_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?State $nextState = null;

    /**
     * @var Collection|Field[]
     *
     * @ORM\OneToMany(targetEntity="Field", mappedBy="state")
     * @ORM\OrderBy({"position": "ASC"})
     */
    protected Collection $fieldsCollection;

    /**
     * @var Collection|StateRoleTransition[]
     *
     * @ORM\OneToMany(targetEntity="StateRoleTransition", mappedBy="fromState")
     */
    protected Collection $roleTransitionsCollection;

    /**
     * @var Collection|StateGroupTransition[]
     *
     * @ORM\OneToMany(targetEntity="StateGroupTransition", mappedBy="fromState")
     */
    protected Collection $groupTransitionsCollection;

    /**
     * @var Collection|StateResponsibleGroup[]
     *
     * @ORM\OneToMany(targetEntity="StateResponsibleGroup", mappedBy="state")
     */
    protected Collection $responsibleGroupsCollection;

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
            'responsible'       => fn (): string => $this->type === StateType::FINAL ? StateResponsible::REMOVE : $this->responsible,
            'nextState'         => fn (): ?self => $this->type === StateType::FINAL ? null : $this->nextState,
            'isFinal'           => fn (): bool => $this->type === StateType::FINAL,
            'fields'            => fn (): array => $this->fieldsCollection->filter(fn (Field $field) => !$field->isRemoved)->getValues(),
            'roleTransitions'   => fn (): array => $this->roleTransitionsCollection->getValues(),
            'groupTransitions'  => fn (): array => $this->groupTransitionsCollection->getValues(),
            'responsibleGroups' => fn (): array => $this->responsibleGroupsCollection->getValues(),
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
