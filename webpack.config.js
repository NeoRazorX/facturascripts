"use strict";

const path = require("path");
const webpack = require("webpack");
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const BootstrapEntryPoints = require("./webpack.bootstrap.config");

module.exports = {
  entry: {
    app: [
      BootstrapEntryPoints.prod,
      "./Core/View/Assets/js/index.js"
    ]
  },
  output: {
    path: path.resolve(__dirname, "Core/View/Assets/dist"),
    filename: "js/[name].bundle.js"
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: [{
          loader: "babel-loader",
          options: { presets: ["es2015"] },
        }],
      },
      {
        test: /\.scss$/,
        use: ExtractTextPlugin.extract({
          fallback: "style-loader",
          use: ["css-loader", "sass-loader"],
          publicPath: "./Core/View/Assets/"
        }),
      },
      {
        test: /\.css$/,
        use: ExtractTextPlugin.extract({
          use: "css-loader",
          publicPath: "./Core/View/Assets/"
        }),
      },
      {
        test: /\.woff2?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
        // Limiting the size of the woff fonts breaks font-awesome ONLY for the extract text plugin
        // loader: "url?limit=10000"
        use: "url-loader"
      },
      {
        test: /\.(ttf|eot|svg)(\?[\s\S]+)?$/,
        use: "file-loader?name=fonts/[name].[ext]"
      },
      {
        test:/bootstrap-sass[\/\\]assets[\/\\]javascripts[\/\\]/,
        loader: "imports-loader?jQuery=jquery"
      },
    ]
  },
  plugins: [
    new ExtractTextPlugin({
      filename: "/css/[name].bundle.css",
      allChunks: true
    }),
    new webpack.NamedModulesPlugin(),
    new webpack.ProvidePlugin({
      "$": "jquery",
      "jQuery": "jquery",
      "window.jQuery": "jquery"
    }),
    new webpack.BannerPlugin(
      "This file is part of FacturaScripts\n" +
      "Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com\n" +
      "\n" +
      "This program is free software: you can redistribute it and/or modify\n" +
      "it under the terms of the GNU Lesser General Public License as\n" +
      "published by the Free Software Foundation, either version 3 of the\n" +
      "License, or (at your option) any later version.\n" +
      "\n" +
      "This program is distributed in the hope that it will be useful,\n" +
      "but WITHOUT ANY WARRANTY; without even the implied warranty of\n" +
      "MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n" +
      "GNU Lesser General Public License for more details.\n" +
      "\n" +
      "You should have received a copy of the GNU Lesser General Public License\n" +
      "along with this program.  If not, see <http://www.gnu.org/licenses/>."),
  ],
  resolve: {
    alias: {
      "jquery.smartmenus": "smartmenus"
    }
  }
};
