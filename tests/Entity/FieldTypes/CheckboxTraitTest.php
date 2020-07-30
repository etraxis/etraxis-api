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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\CheckboxTrait
 */
class CheckboxTraitTest extends WebTestCase
{
    use ReflectionTrait;

    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $object;
    private CheckboxInterface   $facade;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($this->object, 'id', 1);
        $this->object->name       = 'Test';
        $this->object->isRequired = false;

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asCheckbox
     */
    public function testJsonSerialize()
    {
        $expected = [
            'default' => false,
        ];

        self::assertSame($expected, $this->facade->jsonSerialize());
    }

    /**
     * @covers ::asCheckbox
     */
    public function testValidationConstraints()
    {
        $value = false;
        self::assertCount(0, $this->validator->validate($value, $this->facade->getValidationConstraints($this->translator)));

        $value = true;
        self::assertCount(0, $this->validator->validate($value, $this->facade->getValidationConstraints($this->translator)));
    }

    /**
     * @covers ::asCheckbox
     */
    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $this->facade->setDefaultValue(true);
        self::assertTrue($this->facade->getDefaultValue());
        self::assertSame(1, $this->getProperty($parameters, 'defaultValue'));

        $this->facade->setDefaultValue(false);
        self::assertFalse($this->facade->getDefaultValue());
        self::assertSame(0, $this->getProperty($parameters, 'defaultValue'));
    }
}
