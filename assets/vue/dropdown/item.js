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
 * Item of a dropdown menu.
 *
 * @property {string}  value    Item's value.
 * @property {string}  text     Item's text.
 * @property {boolean} disabled Item's status.
 */
module.exports = class {

    /**
     * Default constructor.
     */
    constructor(value, text, disabled = false) {
        this.value    = value;
        this.text     = text;
        this.disabled = disabled;
    }
};
