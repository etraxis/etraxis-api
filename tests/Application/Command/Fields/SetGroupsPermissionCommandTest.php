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

use eTraxis\Application\Dictionary\FieldPermission;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldGroupPermission;
use eTraxis\Entity\Group;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * @covers \eTraxis\Application\Command\Fields\Handler\SetGroupsPermissionHandler::__invoke
 */
class SetGroupsPermissionCommandTest extends TransactionalTestCase
{
    private FieldRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $managers */
        [/* skipping */, $managers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        /** @var Group $developers */
        [/* skipping */, $developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */, $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        static::assertSame(FieldPermission::READ_WRITE, $this->getPermissionByGroup($field->groupPermissions, $managers->id));
        static::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByGroup($field->groupPermissions, $developers->id));
        static::assertNull($this->getPermissionByGroup($field->groupPermissions, $support->id));

        $command = new SetGroupsPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_ONLY,
            'groups'     => [
                $managers->id,
                $support->id,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        static::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByGroup($field->groupPermissions, $managers->id));
        static::assertNull($this->getPermissionByGroup($field->groupPermissions, $developers->id));
        static::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByGroup($field->groupPermissions, $support->id));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_WRITE,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand([
            'field'      => self::UNKNOWN_ENTITY_ID,
            'permission' => FieldPermission::READ_WRITE,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongGroup()
    {
        $this->expectException(HandlerFailedException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'DESC']);

        $command = new SetGroupsPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_WRITE,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * @param FieldGroupPermission[] $permissions
     * @param int                    $groupId
     *
     * @return null|string
     */
    private function getPermissionByGroup(array $permissions, int $groupId): ?string
    {
        $filtered = array_filter($permissions, fn (FieldGroupPermission $permission) => $permission->group->id === $groupId);
        $result   = count($filtered) === 1 ? reset($filtered) : null;

        return $result === null ? null : $result->permission;
    }
}
