import './bootstrap'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import App from './components/AppShell.vue'
import { useAuthStore } from './stores/auth'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/home' },
        {
            path: '/home',
            component: () => import('./components/WorkspaceSelector.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/w/:workspaceSlug',
            component: () => import('./components/ChatLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                { path: '', redirect: to => `/w/${to.params.workspaceSlug}/channels` },
                { path: 'channels', component: () => import('./components/ChannelBrowser.vue') },
                { path: 'c/:channelId', component: () => import('./components/ChatRoom.vue') },
                { path: 'dm/:userId', component: () => import('./components/ChatRoom.vue'), props: { isDm: true } },
                { path: 'files', component: () => import('./components/FileManager.vue') },
                { path: 'settings', component: () => import('./components/WorkspaceSettings.vue') },
                { path: 'inbox', component: () => import('./components/AgentInbox.vue'), props: route => ({ workspaceSlug: route.params.workspaceSlug }) },
            ],
        },
    ],
})

// ── Auth guard ────────────────────────────────────────────────────
let authResolved = false

router.beforeEach(async (to) => {
    if (!to.meta.requiresAuth) return true

    const auth = useAuthStore()

    // Fetch user once per page load
    if (!authResolved) {
        await auth.fetchUser()
        authResolved = true
    }

    if (!auth.user) {
        // Preserve intended destination so we can redirect after login
        window.location.href = `/login?redirect=${encodeURIComponent(to.fullPath)}`
        return false
    }

    return true
})

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(router)
app.mount('#app')
