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
use eTraxis\Application\Dictionary\StateType;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * Template.
 *
 * @ORM\Table(
 *     name="templates",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"project_id", "name"}),
 *         @ORM\UniqueConstraint(columns={"project_id", "prefix"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="eTraxis\Repository\TemplateRepository")
 * @Assert\UniqueEntity(fields={"project", "name"}, message="template.conflict.name")
 * @Assert\UniqueEntity(fields={"project", "prefix"}, message="template.conflict.prefix")
 *
 * @property-read int                       $id               Unique ID.
 * @property-read Project                   $project          Project of the template.
 * @property      string                    $name             Name of the template.
 * @property      string                    $prefix           Prefix of the template (used as a prefix in ID of issues,
 *                                                            created using this template).
 * @property      null|string               $description      Optional description of the template.
 * @property      null|int                  $criticalAge      When an issue remains opened for more than this amount of days
 *                                                            it's displayed in red in the list of issues.
 * @property      null|int                  $frozenTime       When an issue is closed a user cannot change its state anymore,
 *                                                            but one still can edit its fields, add comments and attach files.
 *                                                            If frozen time is specified it will be allowed to edit the issue this
 *                                                            amount of days after its closure. After that the issue becomes read-only.
 *                                                            If this attribute is not specified, an issue will never become read-only.
 * @property      bool                      $isLocked         Whether the template is locked for edition.
 * @property-read null|State                $initialState     Initial state of the template if present.
 * @property-read State[]                   $states           List of template states.
 * @property-read TemplateRolePermission[]  $rolePermissions  List of template role permissions.
 * @property-read TemplateGroupPermission[] $groupPermissions List of template group permissions.
 */
class Template
{
    use PropertyTrait;

    // Constraints.
    public const MAX_NAME        = 50;
    public const MAX_PREFIX      = 5;
    public const MAX_DESCRIPTION = 100;

    // JSON properties.
    public const JSON_ID          = 'id';
    public const JSON_PROJECT     = 'project';
    public const JSON_NAME        = 'name';
    public const JSON_PREFIX      = 'prefix';
    public const JSON_DESCRIPTION = 'description';
    public const JSON_CRITICAL    = 'critical';
    public const JSON_FROZEN      = 'frozen';
    public const JSON_LOCKED      = 'locked';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="templatesCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="project_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected Project $project;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected string $name;

    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=5)
     */
    protected string $prefix;

    /**
     * @var null|string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    protected ?string $description = null;

    /**
     * @var null|int
     *
     * @ORM\Column(name="critical_age", type="integer", nullable=true)
     */
    protected ?int $criticalAge = null;

    /**
     * @var null|int
     *
     * @ORM\Column(name="frozen_time", type="integer", nullable=true)
     */
    protected ?int $frozenTime = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_locked", type="boolean")
     */
    protected bool $isLocked;

    /**
     * @var Collection|State[]
     *
     * @ORM\OneToMany(targetEntity="State", mappedBy="template")
     * @ORM\OrderBy({"name": "ASC"})
     */
    protected Collection $statesCollection;

    /**
     * @var Collection|TemplateRolePermission[]
     *
     * @ORM\OneToMany(targetEntity="TemplateRolePermission", mappedBy="template")
     */
    protected Collection $rolePermissionsCollection;

    /**
     * @var Collection|TemplateGroupPermission[]
     *
     * @ORM\OneToMany(targetEntity="TemplateGroupPermission", mappedBy="template")
     */
    protected Collection $groupPermissionsCollection;

    /**
     * Creates new template in the specified project.
     *
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->project  = $project;
        $this->isLocked = true;

        $this->statesCollection           = new ArrayCollection();
        $this->rolePermissionsCollection  = new ArrayCollection();
        $this->groupPermissionsCollection = new ArrayCollection();
    }

    /**
     * {@inheritDoc}
     */
    protected function getters(): array
    {
        return [
            'initialState'     => fn (): ?State => $this->statesCollection->filter(fn (State $state) => $state->type === StateType::INITIAL)->first() ?: null,
            'states'           => fn (): array => $this->statesCollection->getValues(),
            'rolePermissions'  => fn (): array => $this->rolePermissionsCollection->getValues(),
            'groupPermissions' => fn (): array => $this->groupPermissionsCollection->getValues(),
        ];
    }
}
