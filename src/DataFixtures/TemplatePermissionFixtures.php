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

namespace eTraxis\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Application\Dictionary\TemplatePermission;
use eTraxis\Entity\TemplateGroupPermission;
use eTraxis\Entity\TemplateRolePermission;

/**
 * Test fixtures for 'Template' entity.
 */
class TemplatePermissionFixtures extends Fixture implements DependentFixtureInterface, FixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            GroupFixtures::class,
            TemplateFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task' => [

                SystemRole::AUTHOR => [
                    TemplatePermission::CREATE_ISSUES,
                    TemplatePermission::EDIT_ISSUES,
                    TemplatePermission::REASSIGN_ISSUES,
                    TemplatePermission::SUSPEND_ISSUES,
                    TemplatePermission::RESUME_ISSUES,
                    TemplatePermission::ADD_COMMENTS,
                    TemplatePermission::PRIVATE_COMMENTS,
                    TemplatePermission::ATTACH_FILES,
                    TemplatePermission::DELETE_FILES,
                    TemplatePermission::ADD_DEPENDENCIES,
                    TemplatePermission::REMOVE_DEPENDENCIES,
                    TemplatePermission::SEND_REMINDERS,
                    TemplatePermission::DELETE_ISSUES,
                ],

                SystemRole::RESPONSIBLE => [
                    TemplatePermission::CREATE_ISSUES,
                    TemplatePermission::EDIT_ISSUES,
                    TemplatePermission::REASSIGN_ISSUES,
                    TemplatePermission::SUSPEND_ISSUES,
                    TemplatePermission::RESUME_ISSUES,
                    TemplatePermission::ADD_COMMENTS,
                    TemplatePermission::PRIVATE_COMMENTS,
                    TemplatePermission::ATTACH_FILES,
                    TemplatePermission::DELETE_FILES,
                    TemplatePermission::ADD_DEPENDENCIES,
                    TemplatePermission::REMOVE_DEPENDENCIES,
                    TemplatePermission::SEND_REMINDERS,
                    TemplatePermission::DELETE_ISSUES,
                ],

                'managers:%s' => [
                    TemplatePermission::VIEW_ISSUES,
                    TemplatePermission::CREATE_ISSUES,
                    TemplatePermission::EDIT_ISSUES,
                    TemplatePermission::REASSIGN_ISSUES,
                    TemplatePermission::SUSPEND_ISSUES,
                    TemplatePermission::RESUME_ISSUES,
                    TemplatePermission::ADD_COMMENTS,
                    TemplatePermission::PRIVATE_COMMENTS,
                    TemplatePermission::ATTACH_FILES,
                    TemplatePermission::DELETE_FILES,
                    TemplatePermission::ADD_DEPENDENCIES,
                    TemplatePermission::REMOVE_DEPENDENCIES,
                    TemplatePermission::SEND_REMINDERS,
                    TemplatePermission::DELETE_ISSUES,
                ],

                'developers:%s' => [
                    TemplatePermission::VIEW_ISSUES,
                    TemplatePermission::CREATE_ISSUES,
                ],

                'support:%s' => [
                    TemplatePermission::CREATE_ISSUES,
                ],
            ],

            'req' => [

                SystemRole::AUTHOR => [
                    TemplatePermission::EDIT_ISSUES,
                    TemplatePermission::ADD_COMMENTS,
                    TemplatePermission::ATTACH_FILES,
                    TemplatePermission::ADD_DEPENDENCIES,
                    TemplatePermission::REMOVE_DEPENDENCIES,
                ],

                SystemRole::RESPONSIBLE => [
                    TemplatePermission::ADD_COMMENTS,
                    TemplatePermission::ATTACH_FILES,
                    TemplatePermission::ADD_DEPENDENCIES,
                    TemplatePermission::REMOVE_DEPENDENCIES,
                ],

                'managers:%s' => [
                    TemplatePermission::VIEW_ISSUES,
                    TemplatePermission::CREATE_ISSUES,
                    TemplatePermission::EDIT_ISSUES,
                    TemplatePermission::REASSIGN_ISSUES,
                    TemplatePermission::SUSPEND_ISSUES,
                    TemplatePermission::RESUME_ISSUES,
                    TemplatePermission::ADD_COMMENTS,
                    TemplatePermission::PRIVATE_COMMENTS,
                    TemplatePermission::ATTACH_FILES,
                    TemplatePermission::DELETE_FILES,
                    TemplatePermission::ADD_DEPENDENCIES,
                    TemplatePermission::REMOVE_DEPENDENCIES,
                    TemplatePermission::SEND_REMINDERS,
                    TemplatePermission::DELETE_ISSUES,
                ],

                'clients:%s' => [
                    TemplatePermission::CREATE_ISSUES,
                ],

                'support:%s' => [
                    TemplatePermission::VIEW_ISSUES,
                    TemplatePermission::PRIVATE_COMMENTS,
                ],

                'staff' => [
                    TemplatePermission::VIEW_ISSUES,
                ],
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            foreach ($data as $tref => $groups) {

                /** @var \eTraxis\Entity\Template $template */
                $template = $this->getReference(sprintf('%s:%s', $tref, $pref));

                foreach ($groups as $gref => $permissions) {

                    if (SystemRole::has($gref)) {
                        foreach ($permissions as $permission) {
                            $rolePermission = new TemplateRolePermission($template, $gref, $permission);
                            $manager->persist($rolePermission);
                        }
                    }
                    else {
                        /** @var \eTraxis\Entity\Group $group */
                        $group = $this->getReference(sprintf($gref, $pref));

                        foreach ($permissions as $permission) {
                            $groupPermission = new TemplateGroupPermission($template, $group, $permission);
                            $manager->persist($groupPermission);
                        }
                    }
                }
            }
        }

        $manager->flush();
    }
}
