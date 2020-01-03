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

namespace eTraxis\Entity\FieldTypes;

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\Field;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\NumberTrait
 */
class NumberTraitTest extends WebTestCase
{
    use ReflectionTrait;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var Field
     */
    private $object;

    /**
     * @var NumberInterface
     */
    private $facade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::NUMBER);
        $this->setProperty($this->object, 'id', 1);

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asNumber
     */
    public function testJsonSerialize()
    {
        $expected = [
            'minimum' => NumberInterface::MIN_VALUE,
            'maximum' => NumberInterface::MAX_VALUE,
            'default' => null,
        ];

        self::assertSame($expected, $this->facade->jsonSerialize());
    }

    /**
     * @covers ::asNumber
     */
    public function testValidationConstraints()
    {
        $this->object->name = 'Custom field';
        $this->facade
            ->setMinimumValue(1)
            ->setMaximumValue(100);

        $errors = $this->validator->validate(1, $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(100, $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(0, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 1 to 100.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(101, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 1 to 100.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(12.34, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('test', $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be a valid number.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::asNumber
     */
    public function testMinimumValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(NumberInterface::MIN_VALUE, NumberInterface::MAX_VALUE);
        $min   = NumberInterface::MIN_VALUE - 1;
        $max   = NumberInterface::MAX_VALUE + 1;

        $this->facade->setMinimumValue($value);
        self::assertSame($value, $this->facade->getMinimumValue());
        self::assertSame($value, $this->getProperty($parameters, 'parameter1'));

        $this->facade->setMinimumValue($min);
        self::assertSame(NumberInterface::MIN_VALUE, $this->facade->getMinimumValue());

        $this->facade->setMinimumValue($max);
        self::assertSame(NumberInterface::MAX_VALUE, $this->facade->getMinimumValue());
    }

    /**
     * @covers ::asNumber
     */
    public function testMaximumValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(NumberInterface::MIN_VALUE, NumberInterface::MAX_VALUE);
        $min   = NumberInterface::MIN_VALUE - 1;
        $max   = NumberInterface::MAX_VALUE + 1;

        $this->facade->setMaximumValue($value);
        self::assertSame($value, $this->facade->getMaximumValue());
        self::assertSame($value, $this->getProperty($parameters, 'parameter2'));

        $this->facade->setMaximumValue($min);
        self::assertSame(NumberInterface::MIN_VALUE, $this->facade->getMaximumValue());

        $this->facade->setMaximumValue($max);
        self::assertSame(NumberInterface::MAX_VALUE, $this->facade->getMaximumValue());
    }

    /**
     * @covers ::asNumber
     */
    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(NumberInterface::MIN_VALUE, NumberInterface::MAX_VALUE);
        $min   = NumberInterface::MIN_VALUE - 1;
        $max   = NumberInterface::MAX_VALUE + 1;

        $this->facade->setDefaultValue($value);
        self::assertSame($value, $this->facade->getDefaultValue());
        self::assertSame($value, $this->getProperty($parameters, 'defaultValue'));

        $this->facade->setDefaultValue($min);
        self::assertSame(NumberInterface::MIN_VALUE, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue($max);
        self::assertSame(NumberInterface::MAX_VALUE, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue(null);
        self::assertNull($this->facade->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }
}
