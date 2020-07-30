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

namespace eTraxis\Application\Command\Users;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates profile info of the current user.
 *
 * @property string $locale     New locale.
 * @property string $theme      New theme.
 * @property bool   $light_mode New theme mode state.
 * @property string $timezone   New timezone.
 */
class UpdateSettingsCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\Locale", "keys"}, strict=true)
     */
    public string $locale;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\Theme", "keys"}, strict=true)
     */
    public string $theme;

    /**
     * @Assert\NotNull
     */
    public bool $light_mode;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\Timezone", "values"}, strict=true)
     */
    public string $timezone;
}
