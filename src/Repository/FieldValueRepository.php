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

namespace eTraxis\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Change;
use eTraxis\Entity\Event;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldValue;
use eTraxis\Entity\Issue;
use eTraxis\Entity\User;

class FieldValueRepository extends ServiceEntityRepository implements Contracts\FieldValueRepositoryInterface
{
    private $changeRepository;
    private $decimalRepository;
    private $stringRepository;
    private $textRepository;
    private $listRepository;
    private $issueRepository;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ManagerRegistry                           $registry,
        Contracts\ChangeRepositoryInterface       $changeRepository,
        Contracts\DecimalValueRepositoryInterface $decimalRepository,
        Contracts\StringValueRepositoryInterface  $stringRepository,
        Contracts\TextValueRepositoryInterface    $textRepository,
        Contracts\ListItemRepositoryInterface     $listRepository,
        Contracts\IssueRepositoryInterface        $issueRepository
    )
    {
        parent::__construct($registry, FieldValue::class);

        $this->changeRepository  = $changeRepository;
        $this->decimalRepository = $decimalRepository;
        $this->stringRepository  = $stringRepository;
        $this->textRepository    = $textRepository;
        $this->listRepository    = $listRepository;
        $this->issueRepository   = $issueRepository;
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(FieldValue $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(FieldValue $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function refresh(FieldValue $entity): void
    {
        $this->getEntityManager()->refresh($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldValue(FieldValue $fieldValue, User $user)
    {
        if ($fieldValue->value !== null) {

            switch ($fieldValue->field->type) {

                case FieldType::CHECKBOX:

                    return $fieldValue->value ? true : false;

                case FieldType::DATE:

                    $date = date_create(null, timezone_open($user->timezone) ?: null);
                    $date->setTimestamp($fieldValue->value);

                    return $date->format('Y-m-d');

                case FieldType::DECIMAL:

                    /** @var \eTraxis\Entity\DecimalValue $value */
                    $value = $this->decimalRepository->find($fieldValue->value);

                    return $value === null ? null : $value->value;

                case FieldType::DURATION:

                    /** @var \eTraxis\Entity\FieldTypes\DurationInterface $facade */
                    $facade = $fieldValue->field->getFacade($this->getEntityManager());

                    return $facade->toString($fieldValue->value);

                case FieldType::ISSUE:
                case FieldType::NUMBER:

                    return $fieldValue->value;

                case FieldType::LIST:

                    /** @var \eTraxis\Entity\ListItem $value */
                    $value = $this->listRepository->find($fieldValue->value);

                    return $value === null ? null : $value->value;

                case FieldType::STRING:

                    /** @var \eTraxis\Entity\StringValue $value */
                    $value = $this->stringRepository->find($fieldValue->value);

                    return $value === null ? null : $value->value;

                case FieldType::TEXT:

                    /** @var \eTraxis\Entity\TextValue $value */
                    $value = $this->textRepository->find($fieldValue->value);

                    return $value === null ? null : $value->value;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setFieldValue(Issue $issue, Event $event, Field $field, $value): ?FieldValue
    {
        $newValue = null;

        if ($value !== null) {

            switch ($field->type) {

                case FieldType::CHECKBOX:
                    $newValue = $value ? 1 : 0;
                    break;

                case FieldType::DATE:
                    $timezone = timezone_open($event->user->timezone) ?: null;
                    $newValue = date_create_from_format('Y-m-d', $value, $timezone)->getTimestamp();
                    break;

                case FieldType::DECIMAL:
                    $newValue = $this->decimalRepository->get($value)->id;
                    break;

                case FieldType::DURATION:
                    /** @var \eTraxis\Entity\FieldTypes\DurationInterface $facade */
                    $facade   = $field->getFacade($this->getEntityManager());
                    $newValue = $facade->toNumber($value);
                    break;

                case FieldType::ISSUE:

                    if ($this->issueRepository->find($value) === null) {
                        return null;
                    }

                    $newValue = $value;
                    break;

                case FieldType::LIST:

                    $item = $this->listRepository->findOneByValue($field, $value);

                    if ($item === null) {
                        return null;
                    }

                    $newValue = $item->id;
                    break;

                case FieldType::STRING:
                    $newValue = $this->stringRepository->get($value)->id;
                    break;

                case FieldType::TEXT:
                    $newValue = $this->textRepository->get($value)->id;
                    break;

                default:
                    $newValue = $value;
            }
        }

        /** @var null|FieldValue $fieldValue */
        $fieldValue = $this->findOneBy([
            'issue' => $issue,
            'field' => $field,
        ]);

        // If value doesn't exist yet, create it; otherwise register a change.
        if ($fieldValue === null) {
            $fieldValue = new FieldValue($issue, $field, $newValue);
            $issue->touch();
        }
        elseif ($fieldValue->value !== $newValue) {
            $change = new Change($event, $field, $fieldValue->value, $newValue);
            $this->changeRepository->persist($change);

            $fieldValue->value = $newValue;
            $issue->touch();
        }

        $this->persist($fieldValue);

        return $fieldValue;
    }
}
