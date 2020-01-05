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

use eTraxis\Application\Hateoas;
use eTraxis\Entity\File;
use eTraxis\Entity\User;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'File' entity.
 */
class FileNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var File $object */
        return [
            File::JSON_ID        => $object->id,
            File::JSON_USER      => [
                User::JSON_ID       => $object->event->user->id,
                User::JSON_EMAIL    => $object->event->user->email,
                User::JSON_FULLNAME => $object->event->user->fullname,
            ],
            File::JSON_TIMESTAMP => $object->event->createdAt,
            File::JSON_NAME      => $object->name,
            File::JSON_SIZE      => $object->size,
            File::JSON_TYPE      => $object->type,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof File;
    }
}
