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
 * Modal dialog.
 */
export default {

    mounted() {
        dialogPolyfill.registerDialog(this.$el);
    },

    props: {

        /**
         * @property {string} Header text.
         */
        header: {
            type: String,
            required: true,
        },
    },

    computed: {

        /**
         * @property {Object} Translation resources.
         */
        i18n: () => window.i18n,
    },

    methods: {

        /**
         * @external Opens the dialog.
         */
        open() {
            this.$el.showModal();
        },

        /**
         * @external Closes the dialog.
         */
        close() {
            this.$el.close();
        },

        /**
         * Cancels the dialog.
         */
        cancel() {
            this.$el.close();
            this.$emit('cancel');
        },
    },
};
