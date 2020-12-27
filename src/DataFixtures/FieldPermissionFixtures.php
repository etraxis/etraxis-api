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
use eTraxis\Application\Dictionary\FieldPermission;
use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Entity\FieldGroupPermission;
use eTraxis\Entity\FieldRolePermission;

/**
 * Test fixtures for 'Field' entity.
 */
class FieldPermissionFixtures extends Fixture implements DependentFixtureInterface, FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            GroupFixtures::class,
            FieldFixtures::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'new:%s:priority' => [
                SystemRole::AUTHOR => FieldPermission::READ_ONLY,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'new:%s:description' => [
                SystemRole::AUTHOR => FieldPermission::READ_WRITE,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'new:%s:error' => [
                SystemRole::AUTHOR => FieldPermission::READ_WRITE,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'new:%s:new feature' => [
                SystemRole::AUTHOR => FieldPermission::READ_WRITE,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'assigned:%s:due date' => [
                SystemRole::RESPONSIBLE => FieldPermission::READ_ONLY,
                'managers:%s'           => FieldPermission::READ_WRITE,
            ],

            'completed:%s:commit id' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_WRITE,
            ],

            'completed:%s:delta' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_WRITE,
            ],

            'completed:%s:effort' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_WRITE,
            ],

            'completed:%s:test coverage' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_WRITE,
            ],

            'duplicated:%s:task id' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_ONLY,
            ],

            'duplicated:%s:issue id' => [
                SystemRole::AUTHOR => FieldPermission::READ_ONLY,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'submitted:%s:details' => [
                SystemRole::AUTHOR => FieldPermission::READ_WRITE,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'support:%s'       => FieldPermission::READ_ONLY,
                'staff'            => FieldPermission::READ_ONLY,
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            foreach ($data as $fref => $groups) {

                /** @var \eTraxis\Entity\Field $field */
                $field = $this->getReference(sprintf($fref, $pref));

                foreach ($groups as $gref => $permission) {

                    if (SystemRole::has($gref)) {
                        $rolePermission = new FieldRolePermission($field, $gref, $permission);
                        $manager->persist($rolePermission);
                    }
                    else {
                        /** @var \eTraxis\Entity\Group $group */
                        $group = $this->getReference(sprintf($gref, $pref));

                        $groupPermission = new FieldGroupPermission($field, $group, $permission);
                        $manager->persist($groupPermission);
                    }
                }
            }
        }

        $manager->flush();
    }
}
