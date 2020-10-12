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

import column    from 'components/datatable/column.vue';
import datatable from 'components/datatable/datatable.vue';
import url       from 'utilities/url';

import { PROVIDER_ETRAXIS   } from 'utilities/const';
import { PROVIDER_LDAP      } from 'utilities/const';
import { PROVIDER_GOOGLE    } from 'utilities/const';
import { PROVIDER_GITHUB    } from 'utilities/const';
import { PROVIDER_BITBUCKET } from 'utilities/const';

/**
 * "Users" page.
 */
new Vue({
    el: '#vue-users',

    components: {
        column,
        datatable,
    },

    computed: {

        /**
         * @property {Object} Translation resources.
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} List of possible account permissions.
         */
        permissions: () => ({
            1: i18n['role.admin'],
            0: i18n['role.user'],
        }),

        /**
         * @property {Object} List of possible account providers.
         */
        providers: () => {

            let value = {};

            value[PROVIDER_ETRAXIS]   = 'eTraxis';
            value[PROVIDER_LDAP]      = 'LDAP';
            value[PROVIDER_GOOGLE]    = 'Google';
            value[PROVIDER_GITHUB]    = 'GitHub';
            value[PROVIDER_BITBUCKET] = 'Bitbucket';

            return value;
        },
    },

    methods: {

        /**
         * Data provider for the table.
         *
         * @param  {number}  from    Zero-based index of the first entry to return.
         * @param  {number}  limit   Maximum number of entries to return.
         * @param  {string}  search  Current value of the global search.
         * @param  {Object}  filters Current values of the column filters ([{ "column id": value }]).
         * @param  {Object}  sorting Current sort modes ([{ "column id": "asc"|"desc" }]).
         * @return {Promise} Promise of response.
         */
        users(from, limit, search, filters, sorting) {

            return axios.datatable(url('/api/users'), from, limit, search, filters, sorting, user => {

                let status = null;

                if (user.locked) {
                    status = 'danger';
                }
                else if (user.disabled) {
                    status = 'muted';
                }

                return {
                    DT_id:       user.id,
                    DT_class:    status,
                    fullname:    user.fullname,
                    email:       user.email,
                    admin:       user.admin ? i18n['role.admin'] : i18n['role.user'],
                    provider:    this.providers[user.provider],
                    description: user.description,
                };
            });
        },

        /**
         * A table row is clicked.
         *
         * @param {number} id Account ID.
         */
        viewUser(id) {
            location.href = url('/admin/users/' + id);
        },
    },
});
