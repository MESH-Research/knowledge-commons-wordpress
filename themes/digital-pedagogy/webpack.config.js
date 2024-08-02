const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const WebpackShellPlugin = require('webpack-shell-plugin');
const CompressionWebpackPlugin = require('compression-webpack-plugin');
const EnvConfig = require('./env.js');
module.exports = (env) => {
    const inProduction = env === 'production';
    if(!inProduction) {
        //for local https development -- should not be used in production.
        process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
    }
    const CSSExtract = new MiniCssExtractPlugin({filename: 'style.css'});
    const entry = './src/app.js';
    // const entry = {
    //     index:'./src/app.js',
    //     scrollAnchor: './src/components/mla-scrollable-anchor/index.js',
    //     search: './src/components/SearchResultsPage.js'
    // };
    const output = {
        path: path.join(__dirname, '/dist'),
        filename: 'bundle.js'
    };
    const module = {
        rules: [
            {
                test: /\.(graphql|gql)$/,
                exclude: /node_modules/,
                loader: 'graphql-tag/loader'
            },
            {
                test: /\.mjs$/,
                include: /node_modules/,
                type: "javascript/auto",
            },
            {test: require.resolve("jquery"), loader: "expose-loader?$!expose-loader?jQuery"},
            {test: require.resolve("popper.js"), loader: "expose-loader?$!expose-loader?Popper"},
            {
                loader: 'babel-loader',
                test: /\.js$/,
                exclude: /node_modules/
            },
            {
                test: /\.(png|jpe?g|gif)$/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {},
                    },
                ],
            },
            {
                test: /\.s?css$/,
                use: [
                    MiniCssExtractPlugin.loader, 'import-glob-loader',
                    {
                        loader: 'css-loader',
                        options: {
                            sourceMap: true
                        }
                    }, "postcss-loader",
                    {
                        loader: 'sass-loader',
                        options: {
                            sourceMap: true
                        }
                    }

                ]
            }]
    };
    let plugins = [
        CSSExtract,
        new CompressionWebpackPlugin({
            compressionOptions: {
                numiterations: 15,
            },
            algorithm: "gzip",
            compressionOptions: { level: 9 },
            threshold: 8192,
        }),
    ];
    if(!inProduction) {
        const wps = new WebpackShellPlugin({
            onBuildStart: ['node ' + path.join(__dirname, '/src/graphql/') + 'graphQLBuildSchemaJSON.js'],
        });
        plugins.push(wps);
    }
    const devtool = !inProduction && 'inline-source-map';
    const devServer = {
        port: 8080,
        disableHostCheck: true,
        proxy: {
            '/': {
                path: /./,
                target: EnvConfig.env.DOMAIN,
                secure: false,
                prependPath: false,
            }
        },
        publicPath: EnvConfig.env.DEVELOPMENT_SERVER_PUBLIC_PATH,
        historyApiFallback: true
    };
    return {
        entry,
        output,
        module,
        plugins,
        devtool,
        devServer
    }
};
