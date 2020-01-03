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

namespace eTraxis\Controller\FieldsController;

use eTraxis\Application\Dictionary\FieldPermission;
use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Entity\Field;
use eTraxis\Entity\FieldGroupPermission;
use eTraxis\Entity\FieldRolePermission;
use eTraxis\Entity\Group;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\FieldsController::setPermissions
 */
class SetPermissionsTest extends TransactionalTestCase
{
    public function testSuccessAll()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
            'groups'     => [
                $group->id,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertNotEmpty($roles);
        self::assertNotEmpty($groups);
    }

    public function testSuccessRoles()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertNotEmpty($roles);
        self::assertEmpty($groups);
    }

    public function testSuccessGroups()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'groups'     => [
                $group->id,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertNotEmpty($groups);
    }

    public function testSuccessNone()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
