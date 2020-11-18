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

namespace eTraxis\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;

/**
 * Twig extension for user locale.
 */
class LocaleExtension extends AbstractExtension implements ExtensionInterface
{
    public const LEFT_TO_RIGHT = 'ltr';
    public const RIGHT_TO_LEFT = 'rtl';

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $options = [
            'pre_escape' => 'html',
            'is_safe'    => ['html'],
        ];

        return [
            new TwigFilter('direction', [$this, 'filterDirection'], $options),
        ];
    }

    /**
     * Returns language direction for specified locale.
     *
     * @param string $locale Language code (ISO 639-1) optionally appended with country code (ISO 3166-1).
     *
     * @return string 'ltr'|'rtl'
     */
    public function filterDirection(string $locale): string
    {
        $rtl = ['ar', 'fa', 'he'];

        return in_array(mb_substr($locale, 0, 2), $rtl, true) ? self::RIGHT_TO_LEFT : self::LEFT_TO_RIGHT;
    }
}
