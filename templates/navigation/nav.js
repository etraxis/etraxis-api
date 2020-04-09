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
import menuitem   from './menuitem.vue';

/**
 * Main menu (navigation).
 */
new Vue({
    el: 'nav',

    components: {
        item: menuitem,
    },

    data: {

        /**
         * @property {boolean} Whether the main menu is hidden.
         */
        isMenuHidden: true,
    },

    methods: {

        /**
         * Toggles visibility of the main menu.
         */
        toggleMenu() {
            this.isMenuHidden = !this.isMenuHidden;
        },

        /**
         * Logs the user out.
         */
        logout() {

            this.isMenuHidden = true;

            messagebox.confirm(i18n['confirm.logout'], () => {
                location.href = url('/logout');
            });
        },
    },
});
