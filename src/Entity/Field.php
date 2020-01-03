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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\FieldTypes\FieldInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * Field.
 *
 * @ORM\Table(
 *     name="fields",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"state_id", "name", "removed_at"}),
 *         @ORM\UniqueConstraint(columns={"state_id", "position", "removed_at"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\FieldRepository")
 * @Assert\UniqueEntity(fields={"state", "name", "removedAt"}, message="field.conflict.name", ignoreNull=false)
 *
 * @property-read int                    $id               Unique ID.
 * @property-read State                  $state            State of the field.
 * @property      string                 $name             Name of the field.
 * @property-read string                 $type             Type of the field (see the "FieldType" dictionary).
 * @property      null|string            $description      Optional description of the field.
 * @property      int                    $position         Ordinal number of the field.
 *                                                         No duplicates of this number among fields of the same state are allowed.
 * @property      bool                   $isRequired       Whether the field is required.
 * @property-read bool                   $isRemoved        Whether the field is removed (soft-deleted).
 * @property-read FieldParameters        $parameters       Field parameters (raw values).
 * @property-read FieldRolePermission[]  $rolePermissions  List of field role permissions.
 * @property-read FieldGroupPermission[] $groupPermissions List of field group permissions.
 */
class Field
{
    use PropertyTrait;

    use FieldTypes\NumberTrait;
    use FieldTypes\DecimalTrait;
    use FieldTypes\StringTrait;
    use FieldTypes\TextTrait;
    use FieldTypes\CheckboxTrait;
    use FieldTypes\ListTrait;
    use FieldTypes\IssueTrait;
    use FieldTypes\DateTrait;
    use FieldTypes\DurationTrait;

    // Constraints.
    public const MAX_NAME        = 50;
    public const MAX_DESCRIPTION = 1000;

    // JSON properties.
    public const JSON_ID          = 'id';
    public const JSON_PROJECT     = 'project';
    public const JSON_TEMPLATE    = 'template';
    public const JSON_STATE       = 'state';
    public const JSON_NAME        = 'name';
    public const JSON_TYPE        = 'type';
    public const JSON_DESCRIPTION = 'description';
    public const JSON_POSITION    = 'position';
    public const JSON_REQUIRED    = 'required';
    public const JSON_MINIMUM     = 'minimum';
    public const JSON_MAXIMUM     = 'maximum';
    public const JSON_MAXLENGTH   = 'maxlength';
    public const JSON_DEFAULT     = 'default';
    public const JSON_PCRE        = 'pcre';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="State", inversedBy="fieldsCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="state_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=1000, nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer")
     */
    protected $position;

    /**
     * @var int Unix Epoch timestamp when the field has been removed (NULL while field is present).
     *
     * @ORM\Column(name="removed_at", type="integer", nullable=true)
     */
    protected $removedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_required", type="boolean")
     */
    protected $isRequired;

    /**
     * @var FieldPCRE Perl-compatible regular expression options.
     *
     * @ORM\Embedded(class="FieldPCRE")
     */
    protected $pcre;

    /**
     * @var FieldParameters Field type-specific parameters.
     *
     * @ORM\Embedded(class="FieldParameters", columnPrefix=false)
     */
    protected $parameters;

    /**
     * @var ArrayCollection|FieldRolePermission[]
     *
     * @ORM\OneToMany(targetEntity="FieldRolePermission", mappedBy="field")
     */
    protected $rolePermissionsCollection;

    /**
     * @var ArrayCollection|FieldGroupPermission[]
     *
     * @ORM\OneToMany(targetEntity="FieldGroupPermission", mappedBy="field")
     */
    protected $groupPermissionsCollection;

    /**
     * Creates new field for the specified state.
     *
     * @param State  $state
     * @param string $type
     */
    public function __construct(State $state, string $type)
    {
        if (!FieldType::has($type)) {
            throw new \UnexpectedValueException('Unknown field type: ' . $type);
        }

        $this->state = $state;
        $this->type  = $type;

        $this->pcre       = new FieldPCRE();
        $this->parameters = new FieldParameters();

        $this->rolePermissionsCollection  = new ArrayCollection();
        $this->groupPermissionsCollection = new ArrayCollection();
    }

    /**
     * Marks field as removed (soft-deleted).
     */
    public function remove(): void
    {
        if ($this->removedAt === null) {
            $this->removedAt = time();
        }
    }

    /**
     * Returns field's facade corresponding to its type.
     *
     * @param EntityManagerInterface $manager
     *
     * @return FieldInterface
     */
    public function getFacade(EntityManagerInterface $manager): ?FieldInterface
    {
        switch ($this->type) {

            case FieldType::CHECKBOX:
                return $this->asCheckbox();

            case FieldType::DATE:
                return $this->asDate();

            case FieldType::DECIMAL:
                /** @var \eTraxis\Repository\Contracts\DecimalValueRepositoryInterface $repository */
                $repository = $manager->getRepository(DecimalValue::class);

                return $this->asDecimal($repository);

            case FieldType::DURATION:
                return $this->asDuration();

            case FieldType::ISSUE:
                return $this->asIssue();

            case FieldType::LIST:
                /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $repository */
                $repository = $manager->getRepository(ListItem::class);

                return $this->asList($repository);

            case FieldType::NUMBER:
                return $this->asNumber();

            case FieldType::STRING:
                /** @var \eTraxis\Repository\Contracts\StringValueRepositoryInterface $repository */
                $repository = $manager->getRepository(StringValue::class);

                return $this->asString($repository);

            case FieldType::TEXT:
                /** @var \eTraxis\Repository\Contracts\TextValueRepositoryInterface $repository */
                $repository = $manager->getRepository(TextValue::class);

                return $this->asText($repository);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'isRemoved' => function (): bool {
                return $this->removedAt !== null;
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
