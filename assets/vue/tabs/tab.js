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
 * A single tab.
 */
export default {

    props: {

        /**
         * @property {string} Tab's unique ID.
         */
        id: {
            type: String,
            required: true,
        },

        /**
         * @property {string} Tab's title.
         */
        title: {
            type: String,
            required: true,
        },

        /**
         * @property {number} Optional counter value to be displayed in the caption.
         */
        counter: {
            type: Number,
            default: null,
        },
    },

    data: () => ({

        /**
         * @property {boolean} Whether the tab is active.
         */
        active: false,
    }),

    computed: {

        /**
         * @property {string} Full caption of the tab.
         */
        caption() {
            return this.counter === null
                   ? this.title
                   : `${this.title} (${this.counter})`;
        },
    },
};
