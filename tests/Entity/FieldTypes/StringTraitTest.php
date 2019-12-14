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
use eTraxis\Entity\FieldPCRE;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\StringTrait
 */
class StringTraitTest extends TransactionalTestCase
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
     * @var StringInterface
     */
    private $facade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::STRING);
        $this->setProperty($this->object, 'id', 1);

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asString
     */
    public function testValidationConstraints()
    {
        $this->facade->setMaximumLength(12);
        $this->facade->getPCRE()->check = '(\d{3})-(\d{3})-(\d{4})';

        $errors = $this->validator->validate('123-456-7890', $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('123-456-78901', $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is too long. It should have 12 characters or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('123 456 7890', $this->facade->getValidationConstraints($this->translator));
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
     * @covers ::asString
     */
    public function testMaximumLength()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(StringInterface::MIN_LENGTH, StringInterface::MAX_LENGTH);
        $min   = StringInterface::MIN_LENGTH - 1;
        $max   = StringInterface::MAX_LENGTH + 1;

        $this->facade->setMaximumLength($value);
        self::assertSame($value, $this->facade->getMaximumLength());
        self::assertSame($value, $this->getProperty($parameters, 'parameter1'));

        $this->facade->setMaximumLength($min);
        self::assertSame(StringInterface::MIN_LENGTH, $this->facade->getMaximumLength());

        $this->facade->setMaximumLength($max);
        self::assertSame(StringInterface::MAX_LENGTH, $this->facade->getMaximumLength());
    }

    /**
     * @covers ::asString
     */
    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = 'eTraxis';

        $this->facade->setDefaultValue($value);
        self::assertSame($value, $this->facade->getDefaultValue());
        self::assertNotNull($this->getProperty($parameters, 'defaultValue'));

        $huge = str_pad(null, StringInterface::MAX_LENGTH + 1);
        $trim = str_pad(null, StringInterface::MAX_LENGTH);

        $this->facade->setDefaultValue($huge);
        self::assertSame($trim, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue(null);
        self::assertNull($this->facade->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }

    /**
     * @covers ::asString
     */
    public function testPCRE()
    {
        self::assertInstanceOf(FieldPCRE::class, $this->facade->getPCRE());
    }
}
