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

/** @noinspection PhpUndefinedFieldInspection */

namespace eTraxis\Entity\FieldTypes;

use eTraxis\Application\Seconds;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldParameters;
use eTraxis\Validator\Constraints\DateRange;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Date field trait.
 */
trait DateTrait
{
    /**
     * Returns this field as a field of a "date" type.
     *
     * @return DateInterface
     */
    private function asDate(): DateInterface
    {
        return new class($this, $this->parameters) implements DateInterface {
            private $field;
            private $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param Field           $field
             * @param FieldParameters $parameters
             */
            public function __construct(Field $field, FieldParameters &$parameters)
            {
                $this->field      = $field;
                $this->parameters = &$parameters;
            }

            /**
             * {@inheritdoc}
             */
            public function getValidationConstraints(TranslatorInterface $translator, ?int $timestamp = null): array
            {
                $formatter = new \IntlDateFormatter($translator->getLocale(), \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);

                $now = $timestamp ?? time();

                $message = $translator->trans('field.error.value_range', [
                    '%name%'    => $this->field->name,
                    '%minimum%' => $formatter->format($now + Seconds::ONE_DAY * $this->getMinimumValue()),
                    '%maximum%' => $formatter->format($now + Seconds::ONE_DAY * $this->getMaximumValue()),
                ]);

                $constraints = [
                    new Assert\Regex([
                        'pattern' => DateRange::PCRE_PATTERN,
                    ]),
                    new DateRange([
                        'min'        => date('Y-m-d', $now + Seconds::ONE_DAY * $this->getMinimumValue()),
                        'max'        => date('Y-m-d', $now + Seconds::ONE_DAY * $this->getMaximumValue()),
                        'minMessage' => $message,
                        'maxMessage' => $message,
                    ]),
                ];

                if ($this->field->isRequired) {
                    $constraints[] = new Assert\NotBlank();
                }

                return $constraints;
            }

            /**
             * {@inheritdoc}
             */
            public function setMinimumValue(int $value): DateInterface
            {
                if ($value < DateInterface::MIN_VALUE) {
                    $value = DateInterface::MIN_VALUE;
                }

                if ($value > DateInterface::MAX_VALUE) {
                    $value = DateInterface::MAX_VALUE;
                }

                $this->parameters->parameter1 = $value;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMinimumValue(): int
            {
                return $this->parameters->parameter1 ?? DateInterface::MIN_VALUE;
            }

            /**
             * {@inheritdoc}
             */
            public function setMaximumValue(int $value): DateInterface
            {
                if ($value < DateInterface::MIN_VALUE) {
                    $value = DateInterface::MIN_VALUE;
                }

                if ($value > DateInterface::MAX_VALUE) {
                    $value = DateInterface::MAX_VALUE;
                }

                $this->parameters->parameter2 = $value;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMaximumValue(): int
            {
                return $this->parameters->parameter2 ?? DateInterface::MAX_VALUE;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?int $value): DateInterface
            {
                if ($value !== null) {

                    if ($value < DateInterface::MIN_VALUE) {
                        $value = DateInterface::MIN_VALUE;
                    }

                    if ($value > DateInterface::MAX_VALUE) {
                        $value = DateInterface::MAX_VALUE;
                    }
                }

                $this->parameters->defaultValue = $value;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): ?int
            {
                return $this->parameters->defaultValue;
            }
        };
    }
}
