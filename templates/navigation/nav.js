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
import url        from 'utilities/url';

import menuitem from './menuitem.vue';

/**
 * Main menu (navigation).
 */
new Vue({
    el: 'nav',

    created() {

        // Set current theme mode.
        if (eTraxis.isAnonymous) {
            this.isLightMode = !!JSON.parse(localStorage[this.themeModeStorage] || true);
        }
        else {
            this.isLightMode = eTraxis.isLightMode;
        }

        document.querySelector('html').classList.add(this.themeModeClass);
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
         * @property {string} Name of the local storage variable to store the theme mode.
         */
        themeModeStorage: () => 'eTraxis.isLightMode',

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
            return this.isLightMode ? 'fa-sun-o' : 'fa-moon-o';
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

            if (eTraxis.isAnonymous) {
                localStorage[this.themeModeStorage] = JSON.stringify(this.isLightMode);
            }
            else {
                axios.patch(url('/api/my/profile'), { light_mode: this.isLightMode })
                    .catch(exception => errors(exception));
            }
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
