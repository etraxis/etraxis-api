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

    mounted() {
        // Detect current theme mode.
        let html = document.querySelector('html');
        this.isLightMode = !html.classList.contains('dark');
    },

    components: {
        item: menuitem,
    },

    data: {

        /**
         * @property {boolean} Whether the main menu is hidden.
         */
        isMenuHidden: true,

        /**
         * @property {boolean} Whether the light theme mode is set.
         */
        isLightMode: true,
    },

    computed: {

        /**
         * @property {string} Class name for the current theme mode.
         */
        themeModeClass() {
            return this.isLightMode ? 'light' : 'dark';
        },

        /**
         * @property {string} Icon for the current theme mode.
         */
        themeModeIcon() {
            return this.isLightMode ? 'fa-moon-o' : 'fa-sun-o';
        },
    },

    methods: {

        /**
         * Toggles visibility of the main menu.
         */
        toggleMenu() {
            this.isMenuHidden = !this.isMenuHidden;
        },

        /**
         * Toggles theme mode.
         */
        toggleThemeMode() {

            let html = document.querySelector('html');

            html.classList.remove(this.themeModeClass);
            this.isLightMode = !this.isLightMode;
            html.classList.add(this.themeModeClass);
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
