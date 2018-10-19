var HtmlWebpackPlugin = require('html-webpack-plugin');
var HtmlWebpackIncludeAssetsPlugin = require('html-webpack-include-assets-plugin');
var UglifyJsPlugin = require('uglifyjs-webpack-plugin');
var path = require('path');

var config = {
    entry: {
        app: './compiled_ts/app/main.js',
    },
    output: {
        filename: 'dist/[name].js',
        path: path.resolve(__dirname, './public'),
        publicPath: '',
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
};

module.exports = (env, argv) => {
	// only enable Angular Build Optimizer in production mode to keep development builds fast
    if (argv.mode === 'production') {
        config.module = {
            rules: [
                {
                    test: /\.js$/,
                    loader: '@angular-devkit/build-optimizer/webpack-loader',
                    options: {
                        sourceMap: false,
                    },
                },
            ],
        };
    }

    return config;
};
