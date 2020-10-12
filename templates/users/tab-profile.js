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

import errors from 'utilities/errors';
import ui     from 'utilities/ui';
import url    from 'utilities/url';

/**
 * "Profile" tab.
 */
export default {

    created() {

        ui.block();

        axios.get(url('/api/users/' + this.id))
            .then(response => {
                this.profile = Object.assign({}, response.data);
            })
            .catch(exception => errors(exception))
            .then(() => ui.unblock());
    },

    props: {

        /**
         * @property {number} Account ID.
         */
        id: {
            type: Number,
            required: true,
        },
    },

    data: () => ({

        /**
         * @property {Object} User's profile.
         */
        profile: {
            email: null,
            fullname: null,
            description: null,
            admin: null,
            disabled: null,
            locked: null,
            provider: null,
            locale: null,
            timezone: null,
        },
    }),

    computed: {

        /**
         * @property {Object} Translation resources.
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} Authentication providers.
         */
        providers: () => eTraxis.providers,

        /**
         * @property {Object} Locales.
         */
        locales: () => eTraxis.locales,
    },

    methods: {

        goBack() {
            location.href = url('/admin/users');
        },
    },
};
