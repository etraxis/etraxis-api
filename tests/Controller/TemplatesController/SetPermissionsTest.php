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

namespace eTraxis\Controller\TemplatesController;

use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Application\Dictionary\TemplatePermission;
use eTraxis\Entity\Group;
use eTraxis\Entity\Template;
use eTraxis\Entity\TemplateGroupPermission;
use eTraxis\Entity\TemplateRolePermission;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\TemplatesController::setPermissions
 */
class SetPermissionsTest extends TransactionalTestCase
{
    public function testSuccessAll()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $roles  = array_filter($template->rolePermissions, fn (TemplateRolePermission $permission) => $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES);
        $groups = array_filter($template->groupPermissions, fn (TemplateGroupPermission $permission) => $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES);

        static::assertEmpty($roles);
        static::assertEmpty($groups);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
            'groups'     => [
                $group->id,
            ],
        ];

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        $roles  = array_filter($template->rolePermissions, fn (TemplateRolePermission $permission) => $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES);
        $groups = array_filter($template->groupPermissions, fn (TemplateGroupPermission $permission) => $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES);

        static::assertNotEmpty($roles);
        static::assertNotEmpty($groups);
    }

    public function testSuccessRoles()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $roles  = array_filter($template->rolePermissions, fn (TemplateRolePermission $permission) => $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES);
        $groups = array_filter($template->groupPermissions, fn (TemplateGroupPermission $permission) => $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES);

        static::assertEmpty($roles);
        static::assertEmpty($groups);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        $roles  = array_filter($template->rolePermissions, fn (TemplateRolePermission $permission) => $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES);
        $groups = array_filter($template->groupPermissions, fn (TemplateGroupPermission $permission) => $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES);

        static::assertNotEmpty($roles);
        static::assertEmpty($groups);
    }

    public function testSuccessGroups()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $roles  = array_filter($template->rolePermissions, fn (TemplateRolePermission $permission) => $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES);
        $groups = array_filter($template->groupPermissions, fn (TemplateGroupPermission $permission) => $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES);

        static::assertEmpty($roles);
        static::assertEmpty($groups);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'groups'     => [
                $group->id,
            ],
        ];

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        $roles  = array_filter($template->rolePermissions, fn (TemplateRolePermission $permission) => $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES);
        $groups = array_filter($template->groupPermissions, fn (TemplateGroupPermission $permission) => $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES);

        static::assertEmpty($roles);
        static::assertNotEmpty($groups);
    }

    public function testSuccessNone()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $roles  = array_filter($template->rolePermissions, fn (TemplateRolePermission $permission) => $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES);
        $groups = array_filter($template->groupPermissions, fn (TemplateGroupPermission $permission) => $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES);

        static::assertEmpty($roles);
        static::assertEmpty($groups);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
        ];

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        $roles  = array_filter($template->rolePermissions, fn (TemplateRolePermission $permission) => $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES);
        $groups = array_filter($template->groupPermissions, fn (TemplateGroupPermission $permission) => $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES);

        static::assertEmpty($roles);
        static::assertEmpty($groups);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                'unknown',
            ],
        ];

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/templates/%s/permissions', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_PUT, $uri, $data);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
