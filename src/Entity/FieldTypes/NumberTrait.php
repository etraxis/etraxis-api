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

use eTraxis\Entity\Field;
use eTraxis\Entity\FieldParameters;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Number field trait.
 */
trait NumberTrait
{
    /**
     * Returns this field as a field of a "number" type.
     *
     * @return NumberInterface
     */
    private function asNumber(): NumberInterface
    {
        return new class($this, $this->parameters) implements NumberInterface {
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
                $message = $translator->trans('field.error.value_range', [
                    '%name%'    => $this->field->name,
                    '%minimum%' => $this->getMinimumValue(),
                    '%maximum%' => $this->getMaximumValue(),
                ]);

                $constraints = [
                    new Assert\Range([
                        'min'               => $this->getMinimumValue(),
                        'max'               => $this->getMaximumValue(),
                        'notInRangeMessage' => $message,
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(\-|\+)?\d+$/',
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
            public function setMinimumValue(int $value): NumberInterface
            {
                if ($value < NumberInterface::MIN_VALUE) {
                    $value = NumberInterface::MIN_VALUE;
                }

                if ($value > NumberInterface::MAX_VALUE) {
                    $value = NumberInterface::MAX_VALUE;
                }

                $this->parameters->parameter1 = $value;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMinimumValue(): int
            {
                return $this->parameters->parameter1 ?? NumberInterface::MIN_VALUE;
            }

            /**
             * {@inheritdoc}
             */
            public function setMaximumValue(int $value): NumberInterface
            {
                if ($value < NumberInterface::MIN_VALUE) {
                    $value = NumberInterface::MIN_VALUE;
                }

                if ($value > NumberInterface::MAX_VALUE) {
                    $value = NumberInterface::MAX_VALUE;
                }

                $this->parameters->parameter2 = $value;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMaximumValue(): int
            {
                return $this->parameters->parameter2 ?? NumberInterface::MAX_VALUE;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?int $value): NumberInterface
            {
                if ($value !== null) {

                    if ($value < NumberInterface::MIN_VALUE) {
                        $value = NumberInterface::MIN_VALUE;
                    }

                    if ($value > NumberInterface::MAX_VALUE) {
                        $value = NumberInterface::MAX_VALUE;
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
