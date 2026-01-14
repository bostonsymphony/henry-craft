import { createApp, defineAsyncComponent } from 'vue'
import directionals from './directives/vDirectionals.js'
import scrolllock from './directives/vScrolllock.js'
import { Search, SearchDetail, SearchHistory } from 'henry-search'
import '@vuepic/vue-datepicker/dist/main.css'
import 'vue-select/dist/vue-select.css'
import '@vueform/multiselect/themes/default.css'
import 'henry-search/dist/HenrySearch.css'
import { createGtm } from '@gtm-support/vue-gtm'

// Necessary for Craft vite plugin
import '../styles/index.scss'

const app = createApp({
    components: {
        PAccordion: defineAsyncComponent(() => import('./components/PAccordion.vue')),
        PDismissable: defineAsyncComponent(() => import('./components/PDismissable.vue')),
        PLazy: defineAsyncComponent(() => import('./components/PLazy.vue')),
        POpenable: defineAsyncComponent(() => import('./components/POpenable.vue')),
        PTabs: defineAsyncComponent(() => import('./components/PTabs.vue')),
        PDirectionalKeys: defineAsyncComponent(() => import('./components/PDirectionalKeys.vue')),

        PSelect: defineAsyncComponent(() => import('@vueform/multiselect/themes/default.css') && import('@vueform/multiselect')),
        PSlider: defineAsyncComponent(() => import('./components/PSlider.vue')),
        PYouTube: defineAsyncComponent(() => import('./components/PYouTube.vue')),

        ElementCollection: defineAsyncComponent(() => import('./components/ElementCollection.vue')),
        Search,
        SearchDetail,
        SearchHistory
    },
    directives: {
        directionals,
        scrolllock,
    },
    methods: {
        pushDataLayer (obj) {
            console.log(obj)
            if (this.$gtm.enabled()) {
                window.dataLayer?.push(obj)
                console.log('pushed')
            }
        },

    }
})

app.use(
    createGtm({
        id: 'GTM-WBCG7F', // Your GTM single container ID, array of container ids ['GTM-xxxxxx', 'GTM-yyyyyy'] or array of objects [{id: 'GTM-xxxxxx', queryParams: { gtm_auth: 'abc123', gtm_preview: 'env-4', gtm_cookies_win: 'x'}}, {id: 'GTM-yyyyyy', queryParams: {gtm_auth: 'abc234', gtm_preview: 'env-5', gtm_cookies_win: 'x'}}], // Your GTM single container ID or array of container ids ['GTM-xxxxxx', 'GTM-yyyyyy']
        // queryParams: {
        //   // Add URL query string when loading gtm.js with GTM ID (required when using custom environments)
        //   gtm_auth: 'AB7cDEf3GHIjkl-MnOP8qr',
        //   gtm_preview: 'env-4',
        //   gtm_cookies_win: 'x',
        // },
        source: 'https://www.googletagmanager.com/gtm.js', // Add your own serverside GTM script
        defer: false, // Script can be set to `defer` to speed up page load at the cost of less accurate results (in case visitor leaves before script is loaded, which is unlikely but possible). Defaults to false, so the script is loaded `async` by default
        compatibility: false, // Will add `async` and `defer` to the script tag to not block requests for old browsers that do not support `async`
        enabled: true, // defaults to true. Plugin can be disabled by setting this to false for Ex: enabled: !!GDPR_Cookie (optional)
        debug: true, // Whether or not display console logs debugs (optional)
        loadScript: true, // Whether or not to load the GTM Script (Helpful if you are including GTM manually, but need the dataLayer functionality in your components) (optional)
        trackOnNextTick: false, // Whether or not call trackView in Vue.nextTick
    })

)

app.mount('#app')
