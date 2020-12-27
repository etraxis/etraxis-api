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
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\StringTrait
 */
class StringTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $object;
    private StringInterface     $facade;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::STRING);
        $this->setProperty($this->object, 'id', 1);
        $this->object->isRequired = false;

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asString
     */
    public function testJsonSerialize()
    {
        $expected = [
            'maxlength' => StringInterface::MAX_LENGTH,
            'default'   => null,
            'pcre'      => [
                'check'   => null,
                'search'  => null,
                'replace' => null,
            ],
        ];

        static::assertSame($expected, $this->facade->jsonSerialize());
    }

    /**
     * @covers ::asString
     */
    public function testValidationConstraints()
    {
        $this->facade->setMaximumLength(12);
        $this->facade->getPCRE()->check = '(\d{3})-(\d{3})-(\d{4})';

        $errors = $this->validator->validate('123-456-7890', $this->facade->getValidationConstraints($this->translator));
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('123-456-78901', $this->facade->getValidationConstraints($this->translator));
        static::assertNotCount(0, $errors);
        static::assertSame('This value is too long. It should have 12 characters or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('123 456 7890', $this->facade->getValidationConstraints($this->translator));
        static::assertNotCount(0, $errors);
        static::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        static::assertNotCount(0, $errors);
        static::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        static::assertCount(0, $errors);
    }

    /**
     * @covers ::asString
     */
    public function testMaximumLength()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(StringInterface::MIN_LENGTH, StringInterface::MAX_LENGTH);
        $min   = StringInterface::MIN_LENGTH - 1;
        $max   = StringInterface::MAX_LENGTH + 1;

        $this->facade->setMaximumLength($value);
        static::assertSame($value, $this->facade->getMaximumLength());
        static::assertSame($value, $this->getProperty($parameters, 'parameter1'));

        $this->facade->setMaximumLength($min);
        static::assertSame(StringInterface::MIN_LENGTH, $this->facade->getMaximumLength());

        $this->facade->setMaximumLength($max);
        static::assertSame(StringInterface::MAX_LENGTH, $this->facade->getMaximumLength());
    }

    /**
     * @covers ::asString
     */
    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = 'eTraxis';

        $this->facade->setDefaultValue($value);
        static::assertSame($value, $this->facade->getDefaultValue());
        static::assertNotNull($this->getProperty($parameters, 'defaultValue'));

        $huge = str_pad(null, StringInterface::MAX_LENGTH + 1);
        $trim = str_pad(null, StringInterface::MAX_LENGTH);

        $this->facade->setDefaultValue($huge);
        static::assertSame($trim, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue(null);
        static::assertNull($this->facade->getDefaultValue());
        static::assertNull($this->getProperty($parameters, 'defaultValue'));
    }

    /**
     * @covers ::asString
     */
    public function testPCRE()
    {
        static::assertInstanceOf(FieldPCRE::class, $this->facade->getPCRE());
    }
}
