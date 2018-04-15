var HtmlWebpackPlugin = require('html-webpack-plugin');
var HtmlWebpackIncludeAssetsPlugin = require('html-webpack-include-assets-plugin');
var UglifyJsPlugin = require('uglifyjs-webpack-plugin');
var path = require('path');
var rxPaths = require('rxjs/_esm5/path-mapping');

module.exports = function(env) {
    return [
        {
            entry: {
                app: './compiled_ts/app/main.js',
            },
            output: {
                filename: 'dist/[name].js',
                path: path.resolve(__dirname, './public'),
                publicPath: '',
            },
            resolve: {
                alias: rxPaths(),
            },
            optimization: {
                minimizer: [
                    new UglifyJsPlugin({
                        extractComments: true,
                    }),
                ],
            },
            plugins: [
                new HtmlWebpackPlugin({
                    template: 'public/index_template.html',
                    hash: true,
                }),
                new HtmlWebpackIncludeAssetsPlugin({
                    assets: [
                        'css/base_styles.css',
                        'dist/js/core-js/shim.min.js',
                        'dist/js/zone.js/zone.min.js',
                    ],
                    append: false,
                    hash: true,
                }),
            ],
        },
    ];
};
