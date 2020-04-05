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

//----------------------------------------------------------------------
// This file must be included first as it defines some defaults and
// variables which are reused in other scripts.
//----------------------------------------------------------------------

window.eTraxis = {};
window.i18n = window.i18n || {};

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
Vue.options.delimiters = ['${', '}'];
