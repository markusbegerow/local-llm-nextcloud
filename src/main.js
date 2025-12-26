import Vue from 'vue'
import App from './App.vue'

// eslint-disable-next-line
__webpack_public_path__ = OC.linkTo('local-llm-nextcloud', 'js/')

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA

export default new Vue({
	el: '#app-content-wrapper',
	render: h => h(App),
})
