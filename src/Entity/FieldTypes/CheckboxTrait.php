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

/** @noinspection PhpUndefinedFieldInspection */

namespace eTraxis\Entity\FieldTypes;

use eTraxis\Entity\Field;
use eTraxis\Entity\FieldParameters;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checkbox field trait.
 */
trait CheckboxTrait
{
    /**
     * Returns this field as a field of a "checkbox" type.
     *
     * @return CheckboxInterface
     */
    private function asCheckbox(): CheckboxInterface
    {
        return new class($this, $this->parameters) implements CheckboxInterface {
            private Field           $field;
            private FieldParameters $parameters;

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
            public function jsonSerialize()
            {
                return [
                    Field::JSON_DEFAULT => $this->getDefaultValue(),
                ];
            }

            /**
             * {@inheritdoc}
             */
            public function getValidationConstraints(TranslatorInterface $translator, ?int $timestamp = null): array
            {
                return [
                    new Assert\Choice([
                        'choices' => [false, true],
                        'strict'  => true,
                        'message' => $translator->trans('field.error.value_range', [
                            '%name%' => $this->field->name,
                            '%min%'  => 0,
                            '%max%'  => 1,
                        ]),
                    ]),
                ];
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(bool $value): CheckboxInterface
            {
                $this->parameters->defaultValue = $value ? 1 : 0;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): bool
            {
                return (bool) $this->parameters->defaultValue;
            }
        };
    }
}
