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

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Normalizer for a list of constraint violations.
 */
class ConstraintViolationsNormalizer implements NormalizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        $result = [];

        /** @var \Symfony\Component\Validator\ConstraintViolationInterface[] $object */
        foreach ($object as $violation) {
            $result[] = [
                'property' => $violation->getPropertyPath(),
                'value'    => $violation->getInvalidValue(),
                'message'  => $violation->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $data instanceof ConstraintViolationListInterface;
    }
}
