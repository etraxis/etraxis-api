const path = require('path');

module.exports = {

    mode: 'development',

    entry: {
        'etraxis.js': './assets/etraxis.js',
    },

    output: {
        path: path.resolve(__dirname, './public/js'),
        filename: '[name]',
    },

    resolve: {
        alias: {
            utilities: path.resolve(__dirname, './assets/js/'),
        },
    },
};
