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

import tab  from 'components/tabs/tab.vue';
import tabs from 'components/tabs/tabs.vue';

import profile from './tab-profile.vue';

/**
 * A user page.
 */
new Vue({
    el: '#vue-user',

    components: {
        tab,
        tabs,
        profile,
    },

    data: {

        /**
         * @property {string} ID of the current tab.
         */
        tab: null,
    },
});
