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

namespace eTraxis\Application\Dictionary;

use Dictionary\StaticDictionary;
use League\ISO3166\ISO3166;

/**
 * Timezones.
 */
class Timezone extends StaticDictionary
{
    public const FALLBACK = 'UTC';

    /**
     * Returns list of supported countries.
     *
     * @return string[] Keys are ISO 3166-1 codes, values are country names.
     */
    public static function getCountries(): array
    {
        $countries = [];

        $timezones = timezone_identifiers_list();

        foreach ($timezones as $timezone) {

            $location = timezone_location_get(new \DateTimeZone($timezone));
            $code     = $location['country_code'];

            if ($code !== '??') {
                $data             = (new ISO3166())->alpha2($code);
                $country          = $data['name'];
                $countries[$code] = $country;
            }
        }

        asort($countries);

        return $countries;
    }

    /**
     * Returns list of timezone cities for specified country.
     *
     * @param string $country ISO 3166-1 code.
     *
     * @return string[] Keys are IANA database timezones, values are city names.
     */
    public static function getCities(string $country): array
    {
        $cities = [];

        $timezones = timezone_identifiers_list(\DateTimeZone::PER_COUNTRY, $country);

        foreach ($timezones as $timezone) {

            $parts = explode('/', $timezone);

            $cities[$timezone] = str_replace('_', ' ', end($parts));
        }

        asort($cities);

        return $cities;
    }

    /**
     * {@inheritdoc}
     */
    protected static function dictionary(): array
    {
        $timezones = timezone_identifiers_list();

        return $timezones !== false ? array_combine($timezones, $timezones) : [];
    }
}
