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

namespace eTraxis\Serializer;

use eTraxis\Application\Hateoas;
use eTraxis\Entity\User;
use eTraxis\Entity\Watcher;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'Watcher' entity.
 */
class WatcherNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var Watcher $object */
        return [
            User::JSON_ID       => $object->user->id,
            User::JSON_EMAIL    => $object->user->email,
            User::JSON_FULLNAME => $object->user->fullname,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof Watcher;
    }
}
