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

import errors     from 'utilities/errors';
import messagebox from 'utilities/messagebox';
import ui         from 'utilities/ui';
import url        from 'utilities/url';

import { PROVIDER_ETRAXIS } from 'utilities/const';

const delay = 400;

/**
 * "Settings" tab.
 */
export default {

    created() {

        ui.block();

        axios.get(url('/api/my/profile'))
            .then(response => {
                this.profile = Object.assign({}, response.data);
                this.values  = Object.assign({}, response.data);
            })
            .catch(exception => errors(exception))
            .then(() => ui.unblock());

        this.loadCities();
    },

    data: () => ({

        /**
         * @property {string} Currently selected country.
         */
        country: eTraxis.country,

        /**
         * @property {Object} List of available cities of the current country.
         */
        cities: {},

        /**
         * @property {number} Timer to reload list of cities.
         */
        citiesTimer: null,

        /**
         * @property {Object} Current (saved) user's profile.
         */
        profile: {
            provider: null,
            fullname: null,
            email: null,
            locale: null,
            theme: null,
            timezone: null,
        },

        /**
         * @property {Object} Form values.
         */
        values: {
            fullname: null,
            email: null,
            locale: null,
            theme: null,
            timezone: null,
        },

        /**
         * @property {Object} Form errors.
         */
        errors: {
            fullname: null,
            email: null,
            locale: null,
            theme: null,
            timezone: null,
        },
    }),

    computed: {

        /**
         * @property {Object} Translation resources.
         */
        i18n: () => window.i18n,

        /**
         * @property {boolean} Whether the profile information is read-only.
         */
        isReadOnly() {
            return this.profile.provider !== PROVIDER_ETRAXIS;
        },

        /**
         * @property {Object} List of available locales.
         */
        locales: () => eTraxis.locales,

        /**
         * @property {Object} List of available themes.
         */
        themes: () => eTraxis.themes,

        /**
         * @property {Object} List of available countries.
         */
        countries: () => eTraxis.countries,
    },

    methods: {

        /**
         * Loads list of cities of the current country.
         */
        loadCities() {

            clearTimeout(this.timer);

            if (this.country === 'UTC') {

                this.cities = { UTC: 'UTC' };
                this.values.timezone = 'UTC';
            }
            else {

                this.values.timezone = null;

                axios.get(url(`/settings/cities/${this.country}`))
                    .then(response => {
                        this.cities = Object.assign({}, response.data);
                        this.values.timezone = Object.keys(this.cities)[0];
                    })
                    .catch(exception => errors(exception));
            }
        },

        /**
         * Saves the changes.
         */
        saveChanges() {

            ui.block();

            this.errors = {};

            axios.patch(url('/api/my/profile'), this.values)
                .then(() => messagebox.info(i18n['text.changes_saved'], () => {

                    if (this.profile.fullname !== this.values.fullname) {
                        document.querySelector('nav > .username').textContent = this.values.fullname;
                    }

                    if (this.profile.locale !== this.values.locale || this.profile.theme !== this.values.theme) {
                        ui.block();
                        location.reload();
                    }

                    this.profile = Object.assign({}, this.values);
                }))
                .catch(exception => this.errors = errors(exception))
                .then(() => ui.unblock());
        },
    },

    watch: {

        /**
         * Another country is selected.
         */
        country() {
            clearTimeout(this.timer);
            this.timer = setTimeout(this.loadCities, delay);
        },
    },
};
