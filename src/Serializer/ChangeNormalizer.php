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

namespace eTraxis\Serializer;

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Hateoas;
use eTraxis\Entity\Change;
use eTraxis\Entity\Field;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\DecimalValueRepositoryInterface;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use eTraxis\Repository\Contracts\StringValueRepositoryInterface;
use eTraxis\Repository\Contracts\TextValueRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'Change' entity.
 */
class ChangeNormalizer implements NormalizerInterface
{
    /**
     * @var \Doctrine\Persistence\ObjectRepository[]
     */
    private $repositories;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param DecimalValueRepositoryInterface $decimalRepository
     * @param ListItemRepositoryInterface     $listRepository
     * @param StringValueRepositoryInterface  $stringRepository
     * @param TextValueRepositoryInterface    $textRepository
     */
    public function __construct(
        DecimalValueRepositoryInterface $decimalRepository,
        ListItemRepositoryInterface     $listRepository,
        StringValueRepositoryInterface  $stringRepository,
        TextValueRepositoryInterface    $textRepository
    )
    {
        $this->repositories = [
            FieldType::DECIMAL => $decimalRepository,
            FieldType::LIST    => $listRepository,
            FieldType::STRING  => $stringRepository,
            FieldType::TEXT    => $textRepository,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var Change $object */
        $type = $object->field === null ? FieldType::STRING : $object->field->type;

        /** @var \JsonSerializable $oldValue */
        $oldValue = $this->repositories[$type]->find($object->oldValue);

        /** @var \JsonSerializable $newValue */
        $newValue = $this->repositories[$type]->find($object->newValue);

        return [
            Change::JSON_USER      => [
                User::JSON_ID       => $object->event->user->id,
                User::JSON_EMAIL    => $object->event->user->email,
                User::JSON_FULLNAME => $object->event->user->fullname,
            ],
            Change::JSON_TIMESTAMP => $object->event->createdAt,
            Change::JSON_FIELD     => $object->field === null
                ? null
                : [
                    Field::JSON_ID          => $object->field->id,
                    Field::JSON_NAME        => $object->field->name,
                    Field::JSON_TYPE        => $object->field->type,
                    Field::JSON_DESCRIPTION => $object->field->description,
                    Field::JSON_POSITION    => $object->field->position,
                    Field::JSON_REQUIRED    => $object->field->isRequired,
                ],
            Change::JSON_OLD_VALUE => $oldValue->jsonSerialize(),
            Change::JSON_NEW_VALUE => $newValue->jsonSerialize(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof Change;
    }
}
