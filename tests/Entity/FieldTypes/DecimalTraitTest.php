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
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\DecimalTrait
 */
class DecimalTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $object;
    private DecimalInterface    $facade;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::DECIMAL);
        $this->setProperty($this->object, 'id', 1);
        $this->object->isRequired             = false;
        $this->object->parameters->parameter1 = 0;
        $this->object->parameters->parameter2 = 0;

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asDecimal
     */
    public function testJsonSerialize()
    {
        $expected = [
            'minimum' => DecimalInterface::MIN_VALUE,
            'maximum' => DecimalInterface::MAX_VALUE,
            'default' => null,
        ];

        self::assertSame($expected, $this->facade->jsonSerialize());
    }

    /**
     * @covers ::asDecimal
     */
    public function testValidationConstraints()
    {
        $this->object->name = 'Custom field';
        $this->facade
            ->setMinimumValue('0')
            ->setMaximumValue('100');

        $errors = $this->validator->validate('0', $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('100', $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0.0000000000', $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('100.0000000000', $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-0.000000001', $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 0 to 100.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('100.0000000001', $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 0 to 100.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('test', $this->facade->getValidationConstraints($this->translator));
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
     * @covers ::asDecimal
     */
    public function testMinimumValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        $this->facade->setMinimumValue($value);
        self::assertSame($value, $this->facade->getMinimumValue());
        self::assertNotNull($this->getProperty($parameters, 'parameter1'));

        $this->facade->setMinimumValue($min);
        self::assertSame(DecimalInterface::MIN_VALUE, $this->facade->getMinimumValue());

        $this->facade->setMinimumValue($max);
        self::assertSame(DecimalInterface::MAX_VALUE, $this->facade->getMinimumValue());
    }

    /**
     * @covers ::asDecimal
     */
    public function testMaximumValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        $this->facade->setMaximumValue($value);
        self::assertSame($value, $this->facade->getMaximumValue());
        self::assertNotNull($this->getProperty($parameters, 'parameter2'));

        $this->facade->setMaximumValue($min);
        self::assertSame(DecimalInterface::MIN_VALUE, $this->facade->getMaximumValue());

        $this->facade->setMaximumValue($max);
        self::assertSame(DecimalInterface::MAX_VALUE, $this->facade->getMaximumValue());
    }

    /**
     * @covers ::asDecimal
     */
    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        $this->facade->setDefaultValue($value);
        self::assertSame($value, $this->facade->getDefaultValue());
        self::assertNotNull($this->getProperty($parameters, 'defaultValue'));

        $this->facade->setDefaultValue($min);
        self::assertSame(DecimalInterface::MIN_VALUE, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue($max);
        self::assertSame(DecimalInterface::MAX_VALUE, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue(null);
        self::assertNull($this->facade->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }
}
