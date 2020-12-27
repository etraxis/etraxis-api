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
use eTraxis\Entity\FieldPCRE;
use eTraxis\Repository\Contracts\TextValueRepositoryInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Text field trait.
 */
trait TextTrait
{
    /**
     * Returns this field as a field of a "text" type.
     *
     * @param TextValueRepositoryInterface $repository
     *
     * @return TextInterface
     */
    private function asText(TextValueRepositoryInterface $repository): TextInterface
    {
        return new class($repository, $this, $this->pcre, $this->parameters) implements TextInterface {
            private TextValueRepositoryInterface $repository;
            private Field                        $field;
            private FieldPCRE                    $pcre;
            private FieldParameters              $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param TextValueRepositoryInterface $repository
             * @param Field                        $field
             * @param FieldPCRE                    $pcre
             * @param FieldParameters              $parameters
             */
            public function __construct(TextValueRepositoryInterface $repository, Field $field, FieldPCRE $pcre, FieldParameters &$parameters)
            {
                $this->repository = $repository;
                $this->field      = $field;
                $this->pcre       = $pcre;
                $this->parameters = &$parameters;
            }

            /**
             * {@inheritDoc}
             */
            public function jsonSerialize()
            {
                return [
                    Field::JSON_MAXLENGTH => $this->getMaximumLength(),
                    Field::JSON_DEFAULT   => $this->getDefaultValue(),
                    Field::JSON_PCRE      => $this->getPCRE()->jsonSerialize(),
                ];
            }

            /**
             * {@inheritDoc}
             */
            public function getValidationConstraints(TranslatorInterface $translator, ?int $timestamp = null): array
            {
                $constraints = [
                    new Assert\Length([
                        'max' => $this->getMaximumLength(),
                    ]),
                ];

                if ($this->field->isRequired) {
                    $constraints[] = new Assert\NotBlank();
                }

                if ($this->pcre->check) {
                    $constraints[] = new Assert\Regex([
                        'pattern' => sprintf('/^%s$/', $this->pcre->check),
                    ]);
                }

                return $constraints;
            }

            /**
             * {@inheritDoc}
             */
            public function setMaximumLength(int $length): TextInterface
            {
                if ($length < TextInterface::MIN_LENGTH) {
                    $length = TextInterface::MIN_LENGTH;
                }

                if ($length > TextInterface::MAX_LENGTH) {
                    $length = TextInterface::MAX_LENGTH;
                }

                $this->parameters->parameter1 = $length;

                return $this;
            }

            /**
             * {@inheritDoc}
             */
            public function getMaximumLength(): int
            {
                return $this->parameters->parameter1 ?? TextInterface::MAX_LENGTH;
            }

            /**
             * {@inheritDoc}
             */
            public function setDefaultValue(?string $value): TextInterface
            {
                if (mb_strlen($value) > TextInterface::MAX_LENGTH) {
                    $value = mb_substr($value, 0, TextInterface::MAX_LENGTH);
                }

                $this->parameters->defaultValue = ($value === null)
                    ? null
                    : $this->repository->get($value)->id;

                return $this;
            }

            /**
             * {@inheritDoc}
             */
            public function getDefaultValue(): ?string
            {
                if ($this->parameters->defaultValue === null) {
                    return null;
                }

                /** @var \eTraxis\Entity\TextValue $text */
                $text = $this->repository->find($this->parameters->defaultValue);

                return $text->value;
            }

            /**
             * {@inheritDoc}
             */
            public function getPCRE(): FieldPCRE
            {
                return $this->pcre;
            }
        };
    }
}
