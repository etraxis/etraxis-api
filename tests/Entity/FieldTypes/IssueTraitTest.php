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
use eTraxis\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\IssueTrait
 */
class IssueTraitTest extends WebTestCase
{
    use ReflectionTrait;

    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $object;
    private IssueInterface      $facade;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::ISSUE);
        $this->setProperty($this->object, 'id', 1);
        $this->object->isRequired = false;

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asIssue
     */
    public function testJsonSerialize()
    {
        $expected = [
        ];

        self::assertSame($expected, $this->facade->jsonSerialize());
    }

    /**
     * @covers ::asIssue
     */
    public function testValidationConstraints()
    {
        $errors = $this->validator->validate(1, $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(0, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be greater than 0.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(-1, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(12.34, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

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
}
