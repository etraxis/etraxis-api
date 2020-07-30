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

namespace eTraxis\Entity;

use Doctrine\ORM\EntityManager;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\DecimalValueRepositoryInterface;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use eTraxis\Repository\Contracts\StringValueRepositoryInterface;
use eTraxis\Repository\Contracts\TextValueRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\Field
 */
class FieldTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        $field = new Field($state, FieldType::LIST);
        self::assertSame($state, $this->getProperty($field, 'state'));
        self::assertSame(FieldType::LIST, $this->getProperty($field, 'type'));
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown field type: foo');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        new Field($state, 'foo');
    }

    /**
     * @covers ::getFacade
     */
    public function testGetFacade()
    {
        $expected = [
            FieldType::CHECKBOX => FieldTypes\CheckboxInterface::class,
            FieldType::DATE     => FieldTypes\DateInterface::class,
            FieldType::DECIMAL  => FieldTypes\DecimalInterface::class,
            FieldType::DURATION => FieldTypes\DurationInterface::class,
            FieldType::ISSUE    => FieldTypes\IssueInterface::class,
            FieldType::LIST     => FieldTypes\ListInterface::class,
            FieldType::NUMBER   => FieldTypes\NumberInterface::class,
            FieldType::STRING   => FieldTypes\StringInterface::class,
            FieldType::TEXT     => FieldTypes\TextInterface::class,
        ];

        $manager = $this->createMock(EntityManager::class);
        $manager
            ->method('getRepository')
            ->willReturnMap([
                [DecimalValue::class, $this->createMock(DecimalValueRepositoryInterface::class)],
                [ListItem::class, $this->createMock(ListItemRepositoryInterface::class)],
                [StringValue::class, $this->createMock(StringValueRepositoryInterface::class)],
                [TextValue::class, $this->createMock(TextValueRepositoryInterface::class)],
            ]);

        /** @var EntityManager $manager */
        foreach ($expected as $type => $class) {
            $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), $type);
            self::assertInstanceOf($class, $field->getFacade($manager));
        }

        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        $this->setProperty($field, 'type', 'unknown');
        self::assertNull($field->getFacade($manager));
    }

    /**
     * @covers ::getters
     * @covers ::remove
     */
    public function testIsRemoved()
    {
        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        self::assertFalse($field->isRemoved);

        $field->remove();
        self::assertTrue($field->isRemoved);
    }

    /**
     * @covers ::getters
     */
    public function testRolePermissions()
    {
        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        self::assertSame([], $field->rolePermissions);

        /** @var \Doctrine\Common\Collections\Collection $permissions */
        $permissions = $this->getProperty($field, 'rolePermissionsCollection');
        $permissions->add('Role permission A');
        $permissions->add('Role permission B');

        self::assertSame(['Role permission A', 'Role permission B'], $field->rolePermissions);
    }

    /**
     * @covers ::getters
     */
    public function testGroupPermissions()
    {
        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        self::assertSame([], $field->groupPermissions);

        /** @var \Doctrine\Common\Collections\Collection $permissions */
        $permissions = $this->getProperty($field, 'groupPermissionsCollection');
        $permissions->add('Group permission A');
        $permissions->add('Group permission B');

        self::assertSame(['Group permission A', 'Group permission B'], $field->groupPermissions);
    }
}
