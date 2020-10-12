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
 * "Password" tab.
 */
export default {

    data: () => ({

        /**
         * @property {string} Current password.
         */
        currentPassword: null,

        /**
         * @property {string} New password.
         */
        newPassword: null,

        /**
         * @property {string} New password confirmation.
         */
        passwordConfirmation: null,

        /**
         * @property {Object} Form errors.
         */
        errors: {
            password: null,
        },
    }),

    computed: {

        /**
         * @property {Object} Translation resources.
         */
        i18n: () => window.i18n,
    },

    methods: {

        /**
         * Saves new password.
         */
        changePassword() {

            if (this.newPassword !== this.passwordConfirmation) {
                messagebox.alert(i18n['password.dont_match']);
                return;
            }

            ui.block();

            this.errors = {};

            let data = {
                current: this.currentPassword,
                new:     this.newPassword,
            };

            axios.put(url('/api/my/password'), data)
                .then(() => {
                    messagebox.info(i18n['password.changed'], () => {
                        ui.block();
                        location.href = url('/logout');
                    });
                })
                .catch(exception => this.errors = errors(exception))
                .then(() => ui.unblock());
        },
    },
};
