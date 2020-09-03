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

const defaultPageSize = 10;
const refreshDelay    = 400;

/**
 * Data table.
 */
export default {

    created() {
        this.columns = this.$children;
    },

    mounted() {

        // Restore saved table state (paging).
        if (this.paging) {
            this.proxyPage     = parseInt(this.loadState('page')) || 1;
            this.proxyPageSize = parseInt(this.loadState('pageSize')) || defaultPageSize;

            if (this.allowedPageSizes.indexOf(this.proxyPageSize) === -1) {
                this.proxyPageSize = defaultPageSize;
            }
        }
        else {
            this.proxyPage     = 1;
            this.proxyPageSize = defaultPageSize;
        }

        // Restore saved table state (global search).
        this.proxySearch = this.loadState('search') || '';

        // Restore saved table state (filters).
        let filters = this.loadState('filters');

        for (let column of this.columns) {
            if (filters.hasOwnProperty(column.id) && column.filterable) {
                column.proxyFilterValue = filters[column.id];
            }
        }

        // Restore saved table state (sorting).
        let sorting = this.loadState('sorting');

        for (let column of this.columns) {
            if (sorting.hasOwnProperty(column.id) && column.sortable) {
                this.$set(this.proxySorting, column.id, sorting[column.id]);
            }
        }

        // Default sorting.
        if (Object.keys(this.proxySorting).length === 0) {
            this.$set(this.proxySorting, this.columns[0].id, 'asc');
        }
    },

    props: {

        /**
         * @property {string} Table ID to save its state.
         */
        id: {
            type: String,
            required: true,
        },

        /**
         * @property {function} Table rows data provider.
         *
         * The function takes the following parameters:
         * (
         *     from: number     // Zero-based index of the first entry to return.
         *     limit: number    // Maximum number of entries to return.
         *     search: string   // Current value of the global search.
         *     filters: Object  // Current values of the column filters ([{ "column id": value }]).
         *     sorting: Object  // Current sort modes ([{ "column id": "asc"|"desc" }]).
         * )
         *
         * The function must return a promise which resolves an object of the following structure:
         * {
         *     from: number         // Zero-based index of the first returned entry.
         *     to: number           // Zero-based index of the last returned entry.
         *     total: number        // Total number of entries in the source.
         *     data: Array<Object>  // Returned entries.
         * }
         *
         * In case of error the promise should reject with an error message.
         */
        data: {
            type: Function,
            required: true,
        },

        /**
         * @property {boolean} Whether to show no background for the component panel.
         */
        simplified: {
            type: Boolean,
            default: false,
        },

        /**
         * @property {boolean} Whether to emit an event when a table row is clicked.
         */
        clickable: {
            type: Boolean,
            default: true,
        },

        /**
         * @property {boolean} Whether to enable paging.
         */
        paging: {
            type: Boolean,
            default: true,
        },

        /**
         * @property {number} Current page number, one-based.
         */
        page: {
            type: Number,
            default: 1,
        },

        /**
         * @property {number} Page size.
         */
        pageSize: {
            type: Number,
            default: defaultPageSize,
        },

        /**
         * @property {boolean} Whether to show a column with checkboxes.
         */
        checkboxes: {
            type: Boolean,
            default: true,
        },

        /**
         * @property {Array<string>} Checked rows (array of associated IDs).
         */
        checked: {
            type: Array,
            default: () => ([]),
        },

        /**
         * @property {string} Global "Search" value.
         */
        search: {
            type: String,
            default: '',
        },

        /**
         * @property {Object} Current columns sorting.
         */
        sorting: {
            type: Object,
            default: () => ({}),
        },
    },

    data: () => ({

        /**
         * @property {Array<Object>} List of columns.
         */
        columns: [],

        /**
         * @property {Array<Object>} Rows data.
         */
        rows: [],

        /**
         * @property {boolean} Whether the table is blocked from user's interaction.
         */
        blocked: false,

        /**
         * @property {number} Refresh timer.
         */
        timer: null,

        /**
         * @property {number} Manually entered page number, one-based.
         */
        userPage: 0,

        /**
         * @property {number} Internal copy of the "page" property.
         */
        proxyPage: 0,

        /**
         * @property {number} Internal copy of the "pageSize" property.
         */
        proxyPageSize: 0,

        /**
         * @property {number} First row index, zero-based.
         */
        from: 0,

        /**
         * @property {number} Last row index, zero-based.
         */
        to: 0,

        /**
         * @property {number} Total rows.
         */
        total: 0,

        /**
         * @property {Array<string>} Internal copy of the "checked" property.
         */
        proxyChecked: [],

        /**
         * @property {boolean} Whether all rows are checked.
         */
        checkedAll: false,

        /**
         * @property {string} Internal copy of the "search" property.
         */
        proxySearch: '',

        /**
         * @property {Object} Internal copy of the "sorting" property.
         */
        proxySorting: {},
    }),

    computed: {

        /**
         * @property {string} Ascending sorting order.
         */
        sortAsc: () => 'asc',

        /**
         * @property {string} Descending sorting order.
         */
        sortDesc: () => 'desc',

        /**
         * @property {Array<number>} Allowed page sizes.
         */
        allowedPageSizes: () => [defaultPageSize, 20, 50, 100],

        /**
         * @property {string} Status string for the table's footer.
         */
        status() {

            if (this.blocked) {
                return i18n['text.please_wait'];
            }

            return !this.paging || this.total === 0
                   ? null
                   : i18n['table.status']
                       .replace('%from%', this.from + 1)
                       .replace('%to%', this.to + 1)
                       .replace('%total%', this.total);
        },

        /**
         * @property {number} Total number of pages.
         */
        pages() {
            return this.paging ? Math.ceil(this.total / this.proxyPageSize) : 1;
        },

        /**
         * @property {Object} Column filters values.
         */
        filters() {

            let filters = {};

            for (let column of this.columns) {
                if (column.filterable && column.proxyFilterValue.length !== 0) {
                    filters[column.id] = column.proxyFilterValue;
                }
            }

            return filters;
        },

        /**
         * @property {number} Number of filterable columns.
         */
        totalFilters() {
            return this.columns.filter(column => column.filterable).length;
        },

        /**
         * @property {Object<string>} Translation resources.
         */
        i18n: () => i18n,
    },

    methods: {

        /**
         * @external Reloads the table data.
         */
        refresh() {

            clearTimeout(this.timer);
            this.blocked = true;

            let from  = this.paging ? (this.proxyPage - 1) * this.proxyPageSize : 0;
            let limit = this.paging ? this.proxyPageSize : 0;

            let sorting = {};

            for (let key in this.proxySorting) {
                if (this.proxySorting.hasOwnProperty(key)) {
                    sorting[key] = this.proxySorting[key];
                }
            }

            this.data(from, limit, this.proxySearch, this.filters, sorting)
                .then(response => {
                    this.from  = response.from;
                    this.to    = response.to;
                    this.total = response.total;
                    this.rows  = response.data;

                    if (this.proxyPage > this.pages) {
                        this.proxyPage = this.pages || 1;
                    }

                    this.proxyChecked = [];
                    this.checkedAll   = false;
                    this.blocked      = false;
                })
                .catch(error => {
                    messagebox.alert(error, () => {
                        this.blocked = false;
                    });
                });
        },

        /**
         * Reloads the table data with delay.
         */
        refreshWithDelay() {
            clearTimeout(this.timer);
            this.timer = setTimeout(this.refresh, refreshDelay);
        },

        /**
         * Saves specified value to the local storage.
         *
         * @param {string} name  Name to use in the storage.
         * @param {*}      value Value to store.
         */
        saveState(name, value) {

            if (typeof value === 'object') {

                let values = {};

                for (let index in value) {
                    if (value.hasOwnProperty(index)) {
                        values[index] = value[index];
                    }
                }

                localStorage[`DT_${this.name}_${name}`] = JSON.stringify(values);
            }
            else {
                localStorage[`DT_${this.name}_${name}`] = JSON.stringify(value);
            }
        },

        /**
         * Retrieves value from the local storage.
         *
         * @param  {string} name Name used in the storage.
         * @return {*|null} Retrieved value.
         */
        loadState(name) {
            return JSON.parse(localStorage[`DT_${this.name}_${name}`] || null);
        },

        /**
         * Clears all filters.
         */
        resetFilters() {

            this.proxySearch = '';

            for (let column of this.columns) {
                column.proxyFilterValue = '';
            }
        },

        /**
         * Toggles checkbox status of the specified row.
         *
         * @param {string} id ID of the row (`DT_id` property).
         */
        toggleCheck(id) {

            let index = this.proxyChecked.indexOf(id);

            if (index === -1) {
                this.proxyChecked.push(id);
            }
            else {
                this.proxyChecked.splice(index, 1);
            }
        },

        /**
         * Toggles sorting of the clicked column.
         *
         * @param {MouseEvent} event Mouse event.
         * @param {Object}     id    Clicked column ID.
         */
        toggleSorting(event, id) {

            let direction = (this.proxySorting[id] || '') === this.sortAsc ? this.sortDesc : this.sortAsc;

            if (event.ctrlKey) {
                delete this.proxySorting[id];
                this.proxySorting = Object.assign({}, this.proxySorting, { [id]: direction });
            }
            else {
                this.proxySorting = {};
                this.proxySorting[id] = direction;
            }
        },
    },

    watch: {

        /**
         * User entered new page number.
         *
         * @param {number} value New page number.
         */
        userPage(value) {

            if (this.paging) {

                if (typeof value === 'number' && value >= 1 && value <= this.pages) {
                    this.userPage = this.proxyPage = Math.round(value);
                }
                else {
                    this.userPage = this.proxyPage;
                }
            }
        },

        /**
         * Current page is changed by the component.
         *
         * @param {number} value New page number.
         */
        proxyPage(value) {

            this.userPage = value;

            if (this.paging) {
                this.saveState('page', value);
                this.$emit('update:page', value);
                this.refreshWithDelay();
            }
        },

        /**
         * Current page is changed by the parent.
         *
         * @param {number} value New page number.
         */
        page(value) {
            this.proxyPage = value;
        },

        /**
         * Page size is changed by the component.
         *
         * @param {number} value New page size.
         */
        proxyPageSize(value) {

            if (this.paging) {

                if (this.allowedPageSizes.indexOf(value) === -1) {
                    this.proxyPageSize = defaultPageSize;
                    return;
                }

                this.saveState('pageSize', value);
                this.$emit('update:pageSize', value);
                this.refreshWithDelay();
            }
        },

        /**
         * Page size is changed by the parent.
         *
         * @param {number} value New page size.
         */
        pageSize(value) {
            this.proxyPageSize = value;
        },

        /**
         * The set of checked rows is changed by the component.
         *
         * @param {Array<string>} value New set of checked rows (`DT_id` property).
         */
        proxyChecked(value) {

            let rows = this.rows.filter(row => row.DT_checkable !== false);

            if (this.checkedAll && rows.length !== 0 && value.length === rows.length - 1) {
                this.checkedAll = false;
            }

            if (!this.checkedAll && rows.length !== 0 && value.length === rows.length) {
                this.checkedAll = true;
            }

            this.$emit('update:checked', value);
        },

        /**
         * The set of checked rows is changed by the parent.
         *
         * @param {Array<string>} value New set of checked rows (`DT_id` property).
         */
        checked(value) {
            this.proxyChecked = value;
        },

        /**
         * 'Check all' checkbox is toggled.
         *
         * @param {boolean} value New value of the checkbox.
         */
        checkedAll(value) {

            let rows = this.rows.filter(row => row.DT_checkable !== false);

            if (!value && this.proxyChecked.length === rows.length) {
                this.proxyChecked = [];
            }

            if (value) {
                this.proxyChecked = rows.map(row => row.DT_id);
            }
        },

        /**
         * The global search value is changed by the component.
         *
         * @param {string} value New search.
         */
        proxySearch(value) {
            this.saveState('search', value);
            this.$emit('update:search', value);
            this.refreshWithDelay();
        },

        /**
         * The global search value is changed by the parent.
         *
         * @param {string} value New search.
         */
        search(value) {
            this.proxySearch = value;
        },

        /**
         * Filters are changed.
         *
         * @param {Object} value New filters.
         */
        filters(value) {
            this.saveState('filters', value);
            this.refreshWithDelay();
        },

        /**
         * Sorting is changed by the component.
         *
         * @param {Object} value New sorting.
         */
        proxySorting(value) {
            this.saveState('sorting', value);
            this.$emit('update:sorting', value);
            this.refreshWithDelay();
        },

        /**
         * Sorting is changed by the parent.
         *
         * @param {number} value New sorting.
         */
        sorting(value) {
            this.proxySorting = value;
        },
    },
};
