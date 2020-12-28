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

namespace eTraxis\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;

/**
 * A validator for the DecimalRange constraint.
 */
class DecimalRangeValidator extends ConstraintValidator implements ConstraintValidatorInterface
{
    private const PRECISION = 0x7FFFFFFF;

    /**
     * {@inheritDoc}
     *
     * @param null|mixed   $value
     * @param DecimalRange $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value !== null) {

            if (preg_match(DecimalRange::PCRE_PATTERN, $value)) {

                if ($constraint->min !== null && bccomp($value, $constraint->min, self::PRECISION) < 0) {
                    $this->context->addViolation($constraint->minMessage, ['{{ limit }}' => $constraint->min]);
                }

                if ($constraint->max !== null && bccomp($value, $constraint->max, self::PRECISION) > 0) {
                    $this->context->addViolation($constraint->maxMessage, ['{{ limit }}' => $constraint->max]);
                }
            }
            else {
                $this->context->addViolation($constraint->invalidMessage);
            }
        }
    }
}
