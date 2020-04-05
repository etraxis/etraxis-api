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
 * Converts Unix Epoch timestamp to human-readable localized date.
 *
 * @param  {number} timestamp Unix Epoch timestamp.
 * @return {string} Localized date.
 */
exports.date = (timestamp) => {

    let date = new Date(0);
    date.setUTCSeconds(timestamp);

    return date.toLocaleDateString(eTraxis.locale, {
        day:   'numeric',
        month: 'long',
        year:  'numeric',
    });
};

/**
 * Converts Unix Epoch timestamp to human-readable localized time.
 *
 * @param  {number} timestamp Unix Epoch timestamp.
 * @return {string} Localized time.
 */
exports.time = (timestamp) => {

    let date = new Date(0);
    date.setUTCSeconds(timestamp);

    return date.toLocaleTimeString(eTraxis.locale, {
        hour:   'numeric',
        minute: 'numeric',
        second: 'numeric',
    });
};

/**
 * Converts Unix Epoch timestamp to human-readable localized date and time.
 *
 * @param  {number} timestamp Unix Epoch timestamp.
 * @return {string} Localized date and time.
 */
exports.datetime = (timestamp) => {

    let date = new Date(0);
    date.setUTCSeconds(timestamp);

    return date.toLocaleString(eTraxis.locale, {
        day:    'numeric',
        month:  'long',
        year:   'numeric',
        hour:   'numeric',
        minute: 'numeric',
        second: 'numeric',
    });
};
