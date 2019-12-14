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

use eTraxis\Entity\DecimalValue;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldParameters;
use eTraxis\Repository\Contracts\DecimalValueRepositoryInterface;
use eTraxis\Validator\Constraints\DecimalRange;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Decimal field trait.
 */
trait DecimalTrait
{
    /**
     * Returns this field as a field of a "decimal" type.
     *
     * @param DecimalValueRepositoryInterface $repository
     *
     * @return DecimalInterface
     */
    private function asDecimal(DecimalValueRepositoryInterface $repository): DecimalInterface
    {
        return new class($repository, $this, $this->parameters) implements DecimalInterface {
            private $repository;
            private $field;
            private $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param DecimalValueRepositoryInterface $repository
             * @param Field                           $field
             * @param FieldParameters                 $parameters
             */
            public function __construct(DecimalValueRepositoryInterface $repository, Field $field, FieldParameters &$parameters)
            {
                $this->repository = $repository;
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
                    new Assert\Regex([
                        'pattern' => '/^(\-|\+)?\d{1,10}(\.\d{1,10})?$/',
                    ]),
                    new DecimalRange([
                        'min'        => $this->getMinimumValue(),
                        'max'        => $this->getMaximumValue(),
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
            public function setMinimumValue(string $value): DecimalInterface
            {
                if (bccomp($value, DecimalInterface::MIN_VALUE, DecimalValue::PRECISION) < 0) {
                    $value = DecimalInterface::MIN_VALUE;
                }

                if (bccomp($value, DecimalInterface::MAX_VALUE, DecimalValue::PRECISION) > 0) {
                    $value = DecimalInterface::MAX_VALUE;
                }

                $this->parameters->parameter1 = $this->repository->get($value)->id;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMinimumValue(): string
            {
                /** @var DecimalValue $decimal */
                $decimal = $this->repository->find($this->parameters->parameter1);

                return $decimal !== null ? $decimal->value : DecimalInterface::MIN_VALUE;
            }

            /**
             * {@inheritdoc}
             */
            public function setMaximumValue(string $value): DecimalInterface
            {
                if (bccomp($value, DecimalInterface::MIN_VALUE, DecimalValue::PRECISION) < 0) {
                    $value = DecimalInterface::MIN_VALUE;
                }

                if (bccomp($value, DecimalInterface::MAX_VALUE, DecimalValue::PRECISION) > 0) {
                    $value = DecimalInterface::MAX_VALUE;
                }

                $this->parameters->parameter2 = $this->repository->get($value)->id;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMaximumValue(): string
            {
                /** @var DecimalValue $decimal */
                $decimal = $this->repository->find($this->parameters->parameter2);

                return $decimal !== null ? $decimal->value : DecimalInterface::MAX_VALUE;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?string $value): DecimalInterface
            {
                if ($value !== null) {

                    if (bccomp($value, DecimalInterface::MIN_VALUE, DecimalValue::PRECISION) < 0) {
                        $value = DecimalInterface::MIN_VALUE;
                    }

                    if (bccomp($value, DecimalInterface::MAX_VALUE, DecimalValue::PRECISION) > 0) {
                        $value = DecimalInterface::MAX_VALUE;
                    }
                }

                $this->parameters->defaultValue = ($value === null)
                    ? null
                    : $this->repository->get($value)->id;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): ?string
            {
                if ($this->parameters->defaultValue === null) {
                    return null;
                }

                /** @var DecimalValue $decimal */
                $decimal = $this->repository->find($this->parameters->defaultValue);

                return $decimal->value;
            }
        };
    }
}
