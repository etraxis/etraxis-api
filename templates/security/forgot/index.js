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
 * 'Forgot password' page.
 */
new Vue({
    el: '#vue-forgot',

    data: {

        /**
         * @property {Object} Form values.
         */
        values: {
            email: null,
        },

        /**
         * @property {Object} Form errors.
         */
        errors: {
            email: null,
        },
    },

    computed: {

        /**
         * @property {Object<string>} Translation resources.
         */
        i18n: () => window.i18n,
    },

    methods: {

        /**
         * Submits the form.
         */
        submit() {

            ui.block();

            this.errors = {};

            axios.post(url('/forgot'), this.values)
                .then(() => {
                    messagebox.info(i18n['password.forgot.email_sent'], () => {
                        ui.block();
                        location.href = url('/login');
                    });
                })
                .catch(exception => this.errors = errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Goes back to the login page.
         */
        cancel() {
            location.href = url('/login');
        },
    },
});
