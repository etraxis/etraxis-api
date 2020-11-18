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

//----------------------------------------------------------------------
// This file must be included first as it defines some defaults and
// variables which are reused in other scripts.
//----------------------------------------------------------------------

window.eTraxis = {};
window.i18n = window.i18n || {};

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
Vue.options.delimiters = ['${', '}'];

/**
 * Makes an API call for DataTable component.
 *
 * @param  {string}   url      Absolute URL of the call.
 * @param  {number}   from     Zero-based index of the first entry to return.
 * @param  {number}   limit    Maximum number of entries to return.
 * @param  {string}   search   Current value of the global search.
 * @param  {Object}   filters  Current values of the column filters ([{ "column id": value }]).
 * @param  {Object}   sorting  Current sort modes ([{ "column id": "asc"|"desc" }]).
 * @param  {function} callback Callback function to process the received data.
 * @return {Promise}  Promise of response.
 */
axios.datatable = (url, from, limit, search, filters, sorting, callback) => {

    let params = {
        offset: from,
        limit:  limit,
    };

    let headers = {
        'X-Search': encodeURIComponent(search),
        'X-Filter': JSON.stringify(filters),
        'X-Sort':   JSON.stringify(sorting),
    };

    return new Promise((resolve, reject) => {
        axios.get(url, { headers, params })
            .then(response => resolve({
                from:  response.data.from,
                to:    response.data.to,
                total: response.data.total,
                data:  response.data.data.map(entry => callback(entry)),
            }))
            .catch(exception => reject(exception.response.data));
    });
};
