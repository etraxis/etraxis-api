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

namespace eTraxis\Application\Command\Fields;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\Application\Command\Fields\Handler\CreateStringFieldHandler::__invoke
 */
class CreateStringFieldCommandTest extends TransactionalTestCase
{
    private EntityManagerInterface   $manager;
    private FieldRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Subject']);
        static::assertNull($field);

        $command = new CreateStringFieldCommand([
            'state'       => $state->id,
            'name'        => 'Subject',
            'required'    => true,
            'maxlength'   => 100,
            'default'     => 'Message subject',
            'pcreCheck'   => '.+',
            'pcreSearch'  => 'search',
            'pcreReplace' => 'replace',
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Subject']);
        static::assertNotNull($field);
        static::assertSame($result, $field);
        static::assertSame(FieldType::STRING, $field->type);

        /** @var \eTraxis\Entity\FieldTypes\StringInterface $facade */
        $facade = $field->getFacade($this->manager);
        static::assertSame(100, $facade->getMaximumLength());
        static::assertSame('Message subject', $facade->getDefaultValue());
        static::assertSame('.+', $facade->getPCRE()->check);
        static::assertSame('search', $facade->getPCRE()->search);
        static::assertSame('replace', $facade->getPCRE()->replace);
    }
}
