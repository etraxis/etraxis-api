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

import messagebox from 'utilities/messagebox';
import url        from 'utilities/url';

/**
 * Login page.
 */
new Vue({
    el: '#vue-login',

    mounted() {
        if (eTraxis.error) {
            messagebox.alert(eTraxis.error);
        }
    },

    methods: {

        /**
         * Initiates Google OAuth 2.0 authentication process.
         */
        google() {
            location.href = url('/oauth/google');
        },

        /**
         * Initiates GitHub OAuth 2.0 authentication process.
         */
        github() {
            location.href = url('/oauth/github');
        },

        /**
         * Initiates Bitbucket OAuth 2.0 authentication process.
         */
        bitbucket() {
            location.href = url('/oauth/bitbucket');
        },
    },
});
