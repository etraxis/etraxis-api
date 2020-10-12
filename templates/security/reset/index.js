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

/**
 * "Reset password" page.
 */
new Vue({
    el: '#vue-reset',

    data: {

        /**
         * @property {Object} Form values.
         */
        values: {
            password: null,
            confirmation: null,
        },

        /**
         * @property {Object} Form errors.
         */
        errors: {
            password: null,
        },
    },

    computed: {

        /**
         * @property {Object} Translation resources.
         */
        i18n: () => window.i18n,
    },

    methods: {

        /**
         * Submits the form.
         */
        submit() {

            if (this.values.password !== this.values.confirmation) {
                messagebox.alert(i18n['password.dont_match']);
                return;
            }

            ui.block();

            this.errors = {};

            let data = {
                password: this.values.password,
            };

            axios.post(url('/reset/' + eTraxis.token), data)
                .then(() => {
                    messagebox.info(i18n['password.changed'], () => {
                        ui.block();
                        location.href = url('/login');
                    });
                })
                .catch(exception => this.errors = errors(exception))
                .then(() => ui.unblock());
        },
    },
});
