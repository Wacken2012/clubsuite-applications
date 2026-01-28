const { VueLoaderPlugin } = require('vue-loader')
const path = require('path')

module.exports = {
  entry: './src/main.js',
  output: {
    path: path.resolve(__dirname, './js'),
    filename: 'main.js',
  },
  module: {
    rules: [
      { test: /\.vue$/, loader: 'vue-loader' },
      { test: /\.js$/, loader: 'babel-loader', exclude: /node_modules/ },
      { test: /\.css$/, use: ['style-loader', 'css-loader'] },
      { test: /\.scss$/, use: ['style-loader', 'css-loader', 'sass-loader'] },
      {
        test: /\.(png|jpe?g|gif|svg|woff2?|eot|ttf|otf)$/i,
        type: 'asset/resource'
      }
    ]
  },
  plugins: [new VueLoaderPlugin()],
  resolve: {
    alias: { vue$: 'vue/dist/vue.esm.js' },
    extensions: ['.js', '.vue', '.json'],
    fallback: {
      path: false,
      string_decoder: false,
      fs: false
    }
  }
}