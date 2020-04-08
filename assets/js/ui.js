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
 * Blocks UI from user interaction.
 */
exports.block = () => {

    const id = '__etraxis_blockui';

    const template = `
        <dialog id="${id}" class="blockui">
            <main>
                <p>${i18n['text.please_wait']}</p>
            </main>
        </dialog>`;

    if (!document.getElementById(id)) {

        document.querySelector('body').insertAdjacentHTML('beforeend', template);

        const modal = document.getElementById(id);

        dialogPolyfill.registerDialog(modal);

        modal.addEventListener('cancel', event => {
            event.preventDefault();
        });

        modal.addEventListener('close', () => {
            modal.parentNode.removeChild(modal);
        });

        modal.showModal();
    }
};

/**
 * Unblocks UI.
 */
exports.unblock = () => {

    const modal = document.getElementById('__etraxis_blockui');

    if (modal) {
        modal.close();
    }
};
