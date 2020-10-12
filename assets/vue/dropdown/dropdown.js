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

import Item from './item';

/**
 * Button with a dropdown menu.
 */
export default {

    props: {

        /**
         * @property {Array<Item>} List of menu items.
         */
        items: {
            type: Array,
            required: true,
        },
    },

    data: () => ({

        /**
         * @property {boolean} Whether the menu is currently hidden.
         */
        isHidden: true,
    }),

    methods: {

        /**
         * Button is clicked.
         */
        onButtonClick() {
            this.isHidden = !this.isHidden;
        },

        /**
         * Menu item is clicked.
         *
         * @param {Item} item Item's object.
         */
        onItemClick(item) {

            if (!item.disabled) {
                this.isHidden = true;
                this.$emit('click', item);
            }
        },

        /**
         * A document (outside the menu) is clicked.
         */
        onDocumentClick() {
            this.isHidden = true;
        },
    },

    watch: {

        /**
         * The state of the menu is changed.
         *
         * @param {boolean} value New state.
         */
        isHidden(value) {

            if (!value) {
                document.addEventListener('click', this.onDocumentClick, { once: true });
            }
            else {
                document.removeEventListener('click', this.onDocumentClick);
            }
        },
    },
};
