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
use eTraxis\Entity\ListItem;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * List field trait.
 */
trait ListTrait
{
    /**
     * Returns this field as a field of a "list" type.
     *
     * @param ListItemRepositoryInterface $repository
     *
     * @return ListInterface
     */
    private function asList(ListItemRepositoryInterface $repository): ListInterface
    {
        return new class($repository, $this, $this->parameters) implements ListInterface {
            private ListItemRepositoryInterface $repository;
            private Field                       $field;
            private FieldParameters             $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param ListItemRepositoryInterface $repository
             * @param Field                       $field
             * @param FieldParameters             $parameters
             */
            public function __construct(ListItemRepositoryInterface $repository, Field $field, FieldParameters &$parameters)
            {
                $this->repository = $repository;
                $this->field      = $field;
                $this->parameters = &$parameters;
            }

            /**
             * {@inheritdoc}
             */
            public function jsonSerialize()
            {
                $item = $this->getDefaultValue();

                return [
                    Field::JSON_DEFAULT => $item === null ? null : $item->jsonSerialize(),
                ];
            }

            /**
             * {@inheritdoc}
             */
            public function getValidationConstraints(TranslatorInterface $translator, ?int $timestamp = null): array
            {
                $choices = array_map(fn (ListItem $item) => $item->value, $this->repository->findAllByField($this->field));

                $constraints = [
                    new Assert\Regex([
                        'pattern' => '/^\d+$/',
                    ]),
                    new Assert\GreaterThan([
                        'value' => 0,
                    ]),
                    new Assert\Choice([
                        'choices' => $choices,
                        'strict'  => true,
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
            public function setDefaultValue(?ListItem $value): ListInterface
            {
                if ($value === null) {
                    $this->parameters->defaultValue = null;
                }
                elseif ($value->field->id === $this->field->id) {
                    $this->parameters->defaultValue = $value->id;
                }

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): ?ListItem
            {
                if ($this->parameters->defaultValue === null) {
                    return null;
                }

                /** @var ListItem $item */
                $item = $this->repository->find($this->parameters->defaultValue);

                return $item;
            }
        };
    }
}
