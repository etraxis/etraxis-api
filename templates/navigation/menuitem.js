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

/**
 * Item of the main menu.
 */
export default {

    props: {

        /**
         * @property {string} FontAwesome icon.
         */
        icon: {
            type: String,
            required: true,
        },

        /**
         * @property {string} URL of the item. If skipped, the `click` event will be propagated.
         */
        url: {
            type: String,
            default: null,
        },
    },

    computed: {

        /**
         * @property {string} Content of the default slot.
         */
        title() {
            return this.$slots.default[0].text;
        },
    },

    methods: {

        /**
         * Item is clicked.
         */
        onClick(event) {
            if (!this.url) {
                event.preventDefault();
                this.$emit('click', event);
            }
        },
    },
};
