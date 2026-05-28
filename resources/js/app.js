import './bootstrap'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import App from './components/AppShell.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/home',
        },
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
            ],
        },
    ],
})

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
