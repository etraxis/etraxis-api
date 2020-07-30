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

use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\ListTrait
 */
class ListTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $object;
    private ListInterface       $facade;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        /** @var \eTraxis\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(Field::class);

        [$this->object] = $repository->findBy([
            'name' => 'Priority',
        ]);

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asList
     */
    public function testJsonSerialize()
    {
        /** @var ListItem $item */
        $item = $this->doctrine->getRepository(ListItem::class)->findOneBy([
            'field' => $this->object,
            'value' => 2,
        ]);

        $expected = [
            'default' => [
                'id'    => $item->id,
                'value' => 2,
                'text'  => 'normal',
            ],
        ];

        $this->facade->setDefaultValue($item);

        self::assertSame($expected, $this->facade->jsonSerialize());
    }

    /**
     * @covers ::asList
     */
    public function testValidationConstraints()
    {
        $errors = $this->validator->validate(1, $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(3, $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(0, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be greater than 0.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(4, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('The value you selected is not a valid choice.', $errors->get(0)->getMessage());

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

    /**
     * @covers ::asList
     */
    public function testDefaultValue()
    {
        /** @var \eTraxis\Repository\Contracts\FieldRepositoryInterface $fieldRepository */
        $fieldRepository = $this->doctrine->getRepository(Field::class);

        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $itemRepository */
        $itemRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field[] $fields */
        $fields = $fieldRepository->findBy([
            'name' => 'Priority',
        ]);

        /** @var ListItem $item1 */
        $item1 = $itemRepository->findOneBy([
            'field' => $fields[0],
            'value' => 1,
        ]);

        /** @var ListItem $item2 */
        $item2 = $itemRepository->findOneBy([
            'field' => $fields[1],
            'value' => 2,
        ]);

        $parameters = $this->getProperty($fields[0], 'parameters');

        $this->facade->setDefaultValue($item1);
        self::assertSame($item1, $this->facade->getDefaultValue());
        self::assertSame($item1->id, $this->getProperty($parameters, 'defaultValue'));

        $this->facade->setDefaultValue($item2);
        self::assertSame($item1, $this->facade->getDefaultValue());
        self::assertSame($item1->id, $this->getProperty($parameters, 'defaultValue'));

        $this->facade->setDefaultValue(null);
        self::assertNull($this->facade->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }
}
