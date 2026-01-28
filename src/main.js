import Vue from 'vue'
import App from './App.vue'
import { generateUrl } from '@nextcloud/router'

Vue.config.productionTip = false

// Global API base URL
Vue.prototype.$apiUrl = generateUrl('/apps/clubsuite-applications/api')

new Vue({
  render: h => h(App),
}).$mount('#clubsuite-applications-root')
