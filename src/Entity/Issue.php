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
use eTraxis\Application\Seconds;
use Webinarium\PropertyTrait;

/**
 * Issue.
 *
 * @ORM\Table(
 *     name="issues",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"author_id", "created_at"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\IssueRepository")
 *
 * @property-read int          $id           Unique ID.
 * @property-read string       $fullId       Full unique ID with template prefix.
 * @property      string       $subject      Subject of the issue.
 * @property-read Project      $project      Issue project.
 * @property-read Template     $template     Issue template.
 * @property      State        $state        Current state.
 * @property-read User         $author       Author of the issue.
 * @property      null|User    $responsible  Current responsible of the issue.
 * @property-read null|Issue   $origin       Original issue this issue was cloned from (when applicable).
 * @property-read int          $createdAt    Unix Epoch timestamp when the issue has been created.
 * @property-read int          $changedAt    Unix Epoch timestamp when the issue has been changed last time.
 * @property-read null|int     $closedAt     Unix Epoch timestamp when the issue has been closed, if so.
 * @property-read null|int     $resumesAt    Unix Epoch timestamp when the issue will be resumed, if suspended.
 * @property-read int          $age          Number of days the issue remained or remains opened.
 * @property-read bool         $isCloned     Whether the issue was cloned.
 * @property-read bool         $isCritical   Whether the issue is critical (remains opened for too long).
 * @property-read bool         $isFrozen     Whether the issue is frozen (read-only).
 * @property-read bool         $isClosed     Whether the issue is closed.
 * @property-read bool         $isSuspended  Whether the issue is suspended.
 * @property-read Event[]      $events       List of issue events.
 * @property-read FieldValue[] $values       List of field values.
 * @property-read Issue[]      $dependencies List of issue dependencies.
 */
class Issue
{
    use PropertyTrait;

    // Constraints.
    public const MAX_SUBJECT = 250;

    // JSON properties.
    public const JSON_ID               = 'id';
    public const JSON_SUBJECT          = 'subject';
    public const JSON_CREATED_AT       = 'created_at';
    public const JSON_CHANGED_AT       = 'changed_at';
    public const JSON_CLOSED_AT        = 'closed_at';
    public const JSON_AUTHOR           = 'author';
    public const JSON_AUTHOR_NAME      = 'author_name';
    public const JSON_PROJECT          = 'project';
    public const JSON_PROJECT_NAME     = 'project_name';
    public const JSON_TEMPLATE         = 'template';
    public const JSON_TEMPLATE_NAME    = 'template_name';
    public const JSON_STATE            = 'state';
    public const JSON_STATE_NAME       = 'state_name';
    public const JSON_RESPONSIBLE      = 'responsible';
    public const JSON_RESPONSIBLE_NAME = 'responsible_name';
    public const JSON_IS_CLONED        = 'is_cloned';
    public const JSON_AGE              = 'age';
    public const JSON_IS_CRITICAL      = 'is_critical';
    public const JSON_IS_SUSPENDED     = 'is_suspended';
    public const JSON_IS_CLOSED        = 'is_closed';
    public const JSON_DEPENDENCY       = 'dependency';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=250)
     */
    protected $subject;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\State", fetch="EAGER")
     * @ORM\JoinColumn(name="state_id", nullable=false, referencedColumnName="id")
     */
    protected $state;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\User")
     * @ORM\JoinColumn(name="author_id", nullable=false, referencedColumnName="id")
     */
    protected $author;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\User")
     * @ORM\JoinColumn(name="responsible_id", referencedColumnName="id")
     */
    protected $responsible;

    /**
     * @var Issue
     *
     * @ORM\ManyToOne(targetEntity="Issue")
     * @ORM\JoinColumn(name="origin_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $origin;

    /**
     * @var int
     *
     * @ORM\Column(name="created_at", type="integer")
     */
    protected $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(name="changed_at", type="integer")
     */
    protected $changedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="closed_at", type="integer", nullable=true)
     */
    protected $closedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="resumes_at", type="integer", nullable=true)
     */
    protected $resumesAt;

    /**
     * @var ArrayCollection|Event[]
     *
     * @ORM\OneToMany(targetEntity="Event", mappedBy="issue")
     * @ORM\OrderBy({"createdAt": "ASC", "id": "ASC"})
     */
    protected $eventsCollection;

    /**
     * @var ArrayCollection|FieldValue[]
     *
     * @ORM\OneToMany(targetEntity="FieldValue", mappedBy="issue")
     */
    protected $valuesCollection;

    /**
     * @var ArrayCollection|Dependency[]
     *
     * @ORM\OneToMany(targetEntity="Dependency", mappedBy="issue")
     * @ORM\OrderBy({"issue": "ASC"})
     */
    protected $dependenciesCollection;

    /**
     * Creates new issue.
     *
     * @param User       $author
     * @param null|Issue $origin
     */
    public function __construct(User $author, ?self $origin = null)
    {
        $this->author = $author;
        $this->origin = $origin;

        $this->createdAt = $this->changedAt = time();

        $this->eventsCollection       = new ArrayCollection();
        $this->valuesCollection       = new ArrayCollection();
        $this->dependenciesCollection = new ArrayCollection();
    }

    /**
     * Updates the timestamp of when the issue has been changed.
     */
    public function touch(): void
    {
        $this->changedAt = time();
    }

    /**
     * Suspends the issue until specified timestamp.
     *
     * @param int $timestamp Unix Epoch timestamp.
     */
    public function suspend(int $timestamp): void
    {
        $this->resumesAt = $timestamp;
    }

    /**
     * Resumes the issue if suspended.
     */
    public function resume(): void
    {
        $this->resumesAt = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'fullId' => function (): string {
                return sprintf('%s-%03d', $this->state->template->prefix, $this->id);
            },

            'project' => function (): Project {
                return $this->state->template->project;
            },

            'template' => function (): Template {
                return $this->state->template;
            },

            'age' => function (): int {
                return (int) ceil((($this->closedAt ?? time()) - $this->createdAt) / Seconds::ONE_DAY);
            },

            'isCloned' => function (): bool {
                return $this->origin !== null;
            },

            'isCritical' => function (): bool {
                return $this->state->template->criticalAge !== null && $this->closedAt === null
                    ? $this->state->template->criticalAge < $this->age
                    : false;
            },

            'isFrozen' => function (): bool {

                if ($this->state->template->frozenTime !== null && $this->closedAt !== null) {
                    $duration = time() - $this->closedAt;
                    $period   = ceil($duration / Seconds::ONE_DAY);

                    return $this->state->template->frozenTime < $period;
                }

                return false;
            },

            'isClosed' => function (): bool {
                return $this->closedAt !== null;
            },

            'isSuspended' => function (): bool {
                return $this->resumesAt !== null && $this->resumesAt > time();
            },

            'events' => function (): array {
                return $this->eventsCollection->getValues();
            },

            'values' => function (): array {
                return $this->valuesCollection->getValues();
            },

            'dependencies' => function (): array {
                return array_map(function (Dependency $dependency) {
                    return $dependency->dependency;
                }, $this->dependenciesCollection->getValues());
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setters(): array
    {
        return [

            'state' => function (State $value): void {
                if ($this->state === null || $this->state->template === $value->template) {
                    $this->state    = $value;
                    $this->closedAt = $value->type === StateType::FINAL ? time() : null;
                }
                else {
                    throw new \UnexpectedValueException('Unknown state: ' . $value->name);
                }
            },
        ];
    }
}
