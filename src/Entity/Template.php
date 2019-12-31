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
 *     })
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
    protected $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="templatesCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="project_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $project;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=5)
     */
    protected $prefix;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="critical_age", type="integer", nullable=true)
     */
    protected $criticalAge;

    /**
     * @var int
     *
     * @ORM\Column(name="frozen_time", type="integer", nullable=true)
     */
    protected $frozenTime;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_locked", type="boolean")
     */
    protected $isLocked;

    /**
     * @var ArrayCollection|State[]
     *
     * @ORM\OneToMany(targetEntity="State", mappedBy="template")
     * @ORM\OrderBy({"name": "ASC"})
     */
    protected $statesCollection;

    /**
     * @var ArrayCollection|TemplateRolePermission[]
     *
     * @ORM\OneToMany(targetEntity="TemplateRolePermission", mappedBy="template")
     */
    protected $rolePermissionsCollection;

    /**
     * @var ArrayCollection|TemplateGroupPermission[]
     *
     * @ORM\OneToMany(targetEntity="TemplateGroupPermission", mappedBy="template")
     */
    protected $groupPermissionsCollection;

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
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'initialState' => function (): ?State {
                foreach ($this->statesCollection as $state) {
                    if ($state->type === StateType::INITIAL) {
                        return $state;
                    }
                }

                return null;
            },

            'states' => function (): array {
                return $this->statesCollection->getValues();
            },

            'rolePermissions' => function (): array {
                return $this->rolePermissionsCollection->getValues();
            },

            'groupPermissions' => function (): array {
                return $this->groupPermissionsCollection->getValues();
            },
        ];
    }
}
