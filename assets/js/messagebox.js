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
 * Displays error message box (alternative to JavaScript "alert").
 *
 * @param {string}   message   Error message.
 * @param {function} [onClose] Optional handler to call when the message box is closed.
 */
exports.alert = (message, onClose) =>
    messageBox(i18n['error'], message, 'fa-times-circle', 'danger', true, onClose);

/**
 * Displays informational message box (alternative to JavaScript "alert").
 *
 * @param {string}   message   Informational message.
 * @param {function} [onClose] Optional handler to call when the message box is closed.
 */
exports.info = (message, onClose) =>
    messageBox('eTraxis', message, 'fa-info-circle', 'accent', true, onClose);

/**
 * Displays confirmation message box (alternative to JavaScript "confirm").
 *
 * @param {string}   message     Confirmation message.
 * @param {function} [onConfirm] Optional handler to call when the message box is closed with confirmation.
 */
exports.confirm = (message, onConfirm) =>
    messageBox('eTraxis', message, 'fa-question-circle', 'accent', false, onConfirm);

/**
 * @private Displays modal message box.
 *
 * @param {string}   header       Text of the message box header.
 * @param {string}   message      Text of the message box body.
 * @param {string}   iconGlyph    FontAwesome icon class.
 * @param {string}   iconClass    Additional class to apply to the icon.
 * @param {boolean}  singleButton Whether to create one-button ("OK") or two-buttons ("Yes"/"No") box.
 * @param {function} [onClose]    Optional handler to call when the message box is closed.
 */
const messageBox = (header, message, iconGlyph, iconClass, singleButton, onClose) => {

    // Unique ID of the "<dialog>" element.
    const id = '__etraxis_' + Math.random().toString(36).substr(2);

    const buttons = singleButton
                    ? `<button type="button" data-id="yes">${i18n['button.close']}</button>`
                    : `<button type="button" data-id="yes">${i18n['button.yes']}</button>` +
                      `<button type="button" data-id="no">${i18n['button.no']}</button>`;

    const template = `
        <dialog id="${id}" class="messagebox">
            <header>
                <p>${header}</p>
                <span class="fa fa-remove" title="${i18n['button.close']}"></span>
            </header>
            <main>
                <div>
                    <span class="fa-stack fa-3x">
                        <span class="fa fa-stack-1x fa-circle fa-inverse"></span>
                        <span class="fa fa-stack-1x ${iconGlyph} ${iconClass}"></span>
                    </span>
                </div>
                <p>${message}</p>
            </main>
            <footer>
                ${buttons}
            </footer>
        </dialog>`;

    document.querySelector('body').insertAdjacentHTML('beforeend', template);

    const modal = document.getElementById(id);

    dialogPolyfill.registerDialog(modal);

    const btnYes   = modal.querySelector('footer button[data-id="yes"]');
    const btnNo    = modal.querySelector('footer button[data-id="no"]');
    const btnClose = modal.querySelector('header .fa-remove');

    // Button "Yes" is clicked.
    btnYes.addEventListener('click', () => {
        modal.close('yes');
    });

    // Button "No" is clicked.
    if (btnNo) {
        btnNo.addEventListener('click', () => {
            modal.close('no');
        });
    }

    // The "x" button in the header is clicked.
    btnClose.addEventListener('click', () => {
        modal.close('no');
    });

    // "Esc" is pressed.
    modal.addEventListener('cancel', () => {
        modal.returnValue = 'no';
    });

    // Dialog is closed.
    modal.addEventListener('close', () => {
        modal.parentNode.removeChild(modal);

        if (singleButton || modal.returnValue === 'yes') {
            if (typeof onClose === 'function') {
                onClose();
            }
        }
    });

    modal.showModal();
};
