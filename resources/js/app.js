/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

// フラッシュメッセージ表示用
import VueFlashMessage from 'vue-flash-message';
require('vue-flash-message/dist/vue-flash-message.min.css');
Vue.use(VueFlashMessage);

Vue.component('account-list', require('./components/AccountList.vue').default);
Vue.component('account-status-list', require('./components/AccountStatusList.vue').default);
Vue.component('account-setting-screen', require('./components/AccountSettingScreen.vue').default);
Vue.component('reserve-tweet--screen', require('./components/ReserveTweetScreen.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
});
