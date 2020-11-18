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

namespace eTraxis\Entity\FieldTypes;

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldPCRE;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\TextTrait
 */
class TextTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $object;
    private TextInterface       $facade;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::TEXT);
        $this->setProperty($this->object, 'id', 1);
        $this->object->isRequired = false;

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asText
     */
    public function testJsonSerialize()
    {
        $expected = [
            'maxlength' => TextInterface::MAX_LENGTH,
            'default'   => null,
            'pcre'      => [
                'check'   => null,
                'search'  => null,
                'replace' => null,
            ],
        ];

        self::assertSame($expected, $this->facade->jsonSerialize());
    }

    /**
     * @covers ::asText
     */
    public function testValidationConstraints()
    {
        $this->facade->setMaximumLength(2000);
        $this->facade->getPCRE()->check = '(\*+)';

        $errors = $this->validator->validate(str_pad(null, 2000, '*'), $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(str_pad(null, 2001, '*'), $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is too long. It should have 2000 characters or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(str_pad(null, 2000, '-'), $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::asText
     */
    public function testMaximumLength()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(TextInterface::MIN_LENGTH, TextInterface::MAX_LENGTH);
        $min   = TextInterface::MIN_LENGTH - 1;
        $max   = TextInterface::MAX_LENGTH + 1;

        $this->facade->setMaximumLength($value);
        self::assertSame($value, $this->facade->getMaximumLength());
        self::assertSame($value, $this->getProperty($parameters, 'parameter1'));

        $this->facade->setMaximumLength($min);
        self::assertSame(TextInterface::MIN_LENGTH, $this->facade->getMaximumLength());

        $this->facade->setMaximumLength($max);
        self::assertSame(TextInterface::MAX_LENGTH, $this->facade->getMaximumLength());
    }

    /**
     * @covers ::asText
     */
    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = 'eTraxis';

        $this->facade->setDefaultValue($value);
        self::assertSame($value, $this->facade->getDefaultValue());
        self::assertNotNull($this->getProperty($parameters, 'defaultValue'));

        $huge = str_pad(null, TextInterface::MAX_LENGTH + 1);
        $trim = str_pad(null, TextInterface::MAX_LENGTH);

        $this->facade->setDefaultValue($huge);
        self::assertSame($trim, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue(null);
        self::assertNull($this->facade->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }

    /**
     * @covers ::asText
     */
    public function testPCRE()
    {
        self::assertInstanceOf(FieldPCRE::class, $this->facade->getPCRE());
    }
}
