const { defineConfig } = require('@vue/cli-service')

module.exports = {
  chainWebpack: config => {
    config.module
      .rule('vue')
      .use('vue-loader')
      .tap(options => {
        options.compilerOptions = {
          ...(options.compilerOptions || {}),
          isCustomElement: tag => tag === 'ion-icon'
        }
        return options
      })
  }
}
