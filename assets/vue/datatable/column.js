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
 * Data table column.
 */
export default {

    props: {

        /**
         * @property {string} Column ID.
         */
        id: {
            type: String,
            required: true,
        },

        /**
         * @property {boolean} Whether the table can be sorted by this column.
         */
        sortable: {
            type: Boolean,
            default: true,
        },

        /**
         * @property {boolean} Whether the table can be filtered by this column.
         */
        filterable: {
            type: Boolean,
            default: true,
        },

        /**
         * @property {String} Filter's current value.
         */
        filterValue: {
            type: String,
            default: '',
        },

        /**
         * @property {Object} List of allowed filtering options, if applicable.
         */
        filterWith: {
            type: Object,
            default: () => ({}),
        },

        /**
         * @property {string} Desired width of the column.
         */
        width: {
            type: String,
            default: null,
        },
    },

    data: () => ({

        /**
         * @property {string} Internal copy of the "filterValue" property.
         */
        proxyFilterValue: '',
    }),

    computed: {

        /**
         * @property {boolean} Whether the filter is a dropdown list.
         */
        isDropdownFilter() {
            return Object.keys(this.filterWith).length !== 0;
        },
    },

    methods: {

        /**
         * A column's header is clicked.
         *
         * @param {MouseEvent} event Mouse event.
         */
        onClick(event) {
            if (this.sortable) {
                this.$parent.toggleSorting(event, this.id);
            }
        },
    },

    watch: {

        /**
         * Filter's current value is changed by the component.
         *
         * @param {string} value Filter's new value.
         */
        proxyFilterValue(value) {
            this.$emit('update:filterValue', value);
        },

        /**
         * Filter's current value is changed by the parent.
         *
         * @param {string} value Filter's new value.
         */
        filterValue(value) {
            this.proxyFilterValue = value;
        },
    },
};
