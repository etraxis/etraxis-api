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

    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                loader: 'babel-loader',
            },
        ],
    },

    resolve: {
        alias: {
            utilities: path.resolve(__dirname, './assets/js/'),
        },
    },
};
