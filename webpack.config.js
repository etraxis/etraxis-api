const path = require('path');
const VueLoaderPlugin = require('vue-loader/lib/plugin');

module.exports = {

    mode: 'development',

    entry: {
        'etraxis.js':        './assets/etraxis.js',
        'navigation.js':     './templates/navigation/nav.js',
        'security/login.js': './templates/security/login/index.js',
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
            {
                test: /\.vue$/,
                loader: 'vue-loader',
            },
        ],
    },

    resolve: {
        alias: {
            components: path.resolve(__dirname, './assets/vue/'),
            utilities:  path.resolve(__dirname, './assets/js/'),
        },
    },

    plugins: [
        new VueLoaderPlugin(),
    ],
};
