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
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * Group.
 *
 * @ORM\Table(
 *     name="user_groups",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"project_id", "name"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\GroupRepository")
 * @Assert\UniqueEntity(fields={"project", "name"}, message="group.conflict.name", ignoreNull=false)
 *
 * @property-read int          $id          Unique ID.
 * @property-read null|Project $project     Project of the group (NULL if the group is global).
 * @property      string       $name        Name of the group.
 * @property      null|string  $description Optional description of the group.
 * @property-read bool         $isGlobal    Whether the group is a global one.
 * @property-read User[]       $members     List of members.
 */
class Group
{
    use PropertyTrait;

    // Constraints.
    public const MAX_NAME        = 25;
    public const MAX_DESCRIPTION = 100;

    // JSON properties.
    public const JSON_ID          = 'id';
    public const JSON_PROJECT     = 'project';
    public const JSON_NAME        = 'name';
    public const JSON_DESCRIPTION = 'description';
    public const JSON_GLOBAL      = 'global';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @var null|Project
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\Project", inversedBy="groupsCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Project $project = null;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=25)
     */
    protected string $name;

    /**
     * @var null|string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    protected ?string $description = null;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="User", inversedBy="groupsCollection")
     * @ORM\JoinTable(
     *     name="membership",
     *     joinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")})
     * @ORM\OrderBy({"fullname": "ASC", "email": "ASC"})
     */
    protected $membersCollection;

    /**
     * Creates new group in the specified project (NULL creates a global group).
     *
     * @param null|Project $project
     */
    public function __construct(?Project $project = null)
    {
        $this->project = $project;

        $this->membersCollection = new ArrayCollection();
    }

    /**
     * Adds user to the group.
     *
     * @param User $user
     *
     * @return self
     */
    public function addMember(User $user): self
    {
        if (!$this->membersCollection->contains($user)) {
            $this->membersCollection[] = $user;
        }

        return $this;
    }

    /**
     * Removes user from the group.
     *
     * @param User $user
     *
     * @return self
     */
    public function removeMember(User $user): self
    {
        $this->membersCollection->removeElement($user);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [
            'isGlobal' => fn (): bool => $this->project === null,
            'members'  => fn (): array => $this->membersCollection->getValues(),
        ];
    }
}
