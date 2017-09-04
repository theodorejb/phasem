var webpack = require('webpack');
var HtmlWebpackPlugin = require('html-webpack-plugin');
var path = require('path');

module.exports = function(env) {
    return [
        {
            entry: {
                app: './compiled_ts/app/main.js',
            },
            output: {
                filename: 'dist/js/[name].js',
                path: path.resolve(__dirname, './public'),
                publicPath: '',
            },
            plugins: [
                new webpack.optimize.ModuleConcatenationPlugin(),
                new webpack.optimize.UglifyJsPlugin({comments: false}),
                new HtmlWebpackPlugin({
                    template: 'public/index_template.html',
                    hash: true,
                }),
            ],
        },
    ];
};
