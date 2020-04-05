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
 * Returns absolute URL of the specified relative one.
 *
 * @param  {string} url Relative URL (should start with '/').
 * @return {string} Absolute URL.
 */
export default (url) => {

    let homepage = eTraxis.homepage;

    if (homepage.substr(-1) === '/') {
        homepage = homepage.slice(0, -1);
    }

    return homepage + url;
};
