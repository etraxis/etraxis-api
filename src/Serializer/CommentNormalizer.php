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
use eTraxis\Entity\Comment;
use eTraxis\Entity\User;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'Comment' entity.
 */
class CommentNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var Comment $object */
        return [
            Comment::JSON_ID        => $object->id,
            Comment::JSON_USER      => [
                User::JSON_ID       => $object->event->user->id,
                User::JSON_EMAIL    => $object->event->user->email,
                User::JSON_FULLNAME => $object->event->user->fullname,
            ],
            Comment::JSON_TIMESTAMP => $object->event->createdAt,
            Comment::JSON_TEXT      => $object->body,
            Comment::JSON_PRIVATE   => $object->isPrivate,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof Comment;
    }
}
