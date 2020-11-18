//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

/**
 * Set of tabs.
 */
export default {

    created() {
        this.tabs = this.$children;
    },

    mounted() {

        let tab = this.tabs.find(tab => tab.id === this.value);

        if (tab) {
            tab.active = true;
        }
        else {
            this.$emit('input', this.tabs[0].id);
        }
    },

    props: {

        /**
         * @property {string} ID of the active tab.
         */
        value: {
            type: String,
            required: false,
        },
    },

    data: () => ({

        /**
         * @property {Array<Tab>} List of tabs.
         */
        tabs: [],
    }),

    methods: {

        /**
         * Makes the specified tab active.
         *
         * @param {string} id Tab's ID.
         */
        activateTab(id) {
            if (this.value !== id) {
                this.$emit('input', id);
            }
        },
    },

    watch: {

        /**
         * Another tab is activated.
         *
         * @param {string} id Tab's ID.
         */
        value(id) {
            for (let tab of this.tabs) {
                tab.active = (tab.id === id);
            }
        },
    },
};
