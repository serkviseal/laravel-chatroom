<template>
  <div class="flex h-screen bg-gray-950 text-gray-100 overflow-hidden">

    <!-- ── Workspace Sidebar ──────────────────────────────────── -->
    <aside class="w-60 flex-shrink-0 flex flex-col bg-gray-900 border-r border-gray-800">

      <!-- Workspace header -->
      <div class="h-14 px-4 border-b border-gray-800 flex items-center gap-2 flex-shrink-0">
        <img
          v-if="workspaceStore.currentWorkspace?.avatar_url"
          :src="workspaceStore.currentWorkspace.avatar_url"
          class="w-7 h-7 rounded-lg object-cover flex-shrink-0"
          alt=""
        />
        <span class="flex-1 font-bold text-white tracking-tight truncate text-sm">
          {{ workspaceStore.currentWorkspace?.name ?? 'Chatroom' }}
        </span>
        <NotificationBell />
        <button
          @click="router.push('/home')"
          class="p-1 text-gray-500 hover:text-gray-300 transition-colors rounded"
          title="Switch workspace"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
          </svg>
        </button>
      </div>

      <!-- Search bar -->
      <div class="px-3 py-2 flex-shrink-0">
        <button
          @click="showSearch = true"
          class="w-full flex items-center gap-2 px-3 py-1.5 bg-gray-800 hover:bg-gray-750 text-gray-500 rounded-lg text-sm transition-colors border border-gray-700 hover:border-gray-600"
        >
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <span class="flex-1 text-left">Search</span>
          <kbd class="text-xs text-gray-700 font-mono bg-gray-900 px-1 rounded">⌘K</kbd>
        </button>
      </div>

      <!-- Channel list -->
      <RoomList
        class="flex-1 min-h-0"
        :rooms="chat.rooms"
        :active-room-id="chat.activeRoomId"
        @select="selectChannel"
        @join="joinChannel"
        @create="showCreateRoom = true"
        @browse="showChannelBrowser = true"
      />

      <!-- User footer -->
      <div class="flex-shrink-0 border-t border-gray-800 p-2">
        <button
          @click="showUserMenu = !showUserMenu"
          class="w-full flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-gray-800 transition-colors"
        >
          <div class="relative flex-shrink-0">
            <img
              :src="auth.user?.avatar_url"
              :alt="auth.user?.name"
              class="w-8 h-8 rounded-full object-cover"
            />
            <span
              class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-gray-900"
              :class="userStatusDot"
            />
          </div>
          <div class="flex-1 min-w-0 text-left">
            <div class="text-sm text-gray-200 truncate font-medium">{{ auth.user?.name }}</div>
            <div class="text-xs text-gray-500 truncate">{{ currentStatusText }}</div>
          </div>
          <svg class="w-4 h-4 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
          </svg>
        </button>
        <UserStatusMenu v-if="showUserMenu" @close="showUserMenu = false" />
      </div>
    </aside>

    <!-- ── Main area ──────────────────────────────────────────── -->
    <div class="flex flex-1 min-w-0 overflow-hidden">
      <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <router-view v-if="isSubPage" />
        <template v-else>
          <ChatRoom
            v-if="chat.activeRoomId"
            :room="chat.activeRoom"
            @open-thread="openThread"
          />
          <div v-else class="flex-1 flex flex-col items-center justify-center text-center px-8">
            <div class="w-16 h-16 bg-gray-800 rounded-2xl flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-300 mb-1">No channel selected</h3>
            <p class="text-gray-600 text-sm mb-4">Pick a channel from the sidebar or explore what's available.</p>
            <button
              @click="showChannelBrowser = true"
              class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-medium transition-colors"
            >
              Browse channels
            </button>
          </div>
        </template>
      </main>

      <!-- Thread panel -->
      <ThreadPanel v-if="activeThread" :message="activeThread" @close="activeThread = null" />
    </div>

    <!-- ── Modals ─────────────────────────────────────────────── -->
    <CreateRoomModal v-if="showCreateRoom" @close="showCreateRoom = false" @created="onRoomCreated" />
    <ChannelBrowser  v-if="showChannelBrowser" @close="showChannelBrowser = false" @select="selectChannel" />
    <SearchModal     v-if="showSearch" @close="showSearch = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useChatStore } from '@/stores/chat'
import { useWorkspaceStore } from '@/stores/workspace'
import RoomList from './RoomList.vue'
import ChatRoom from './ChatRoom.vue'
import CreateRoomModal from './CreateRoomModal.vue'
import NotificationBell from './NotificationBell.vue'
import ThreadPanel from './ThreadPanel.vue'
import ChannelBrowser from './ChannelBrowser.vue'
import UserStatusMenu from './UserStatusMenu.vue'
import SearchModal from './SearchModal.vue'

const auth           = useAuthStore()
const chat           = useChatStore()
const workspaceStore = useWorkspaceStore()
const router         = useRouter()
const route          = useRoute()

const showCreateRoom    = ref(false)
const showChannelBrowser = ref(false)
const showUserMenu      = ref(false)
const showSearch        = ref(false)
const activeThread      = ref(null)

const isSubPage = computed(() =>
    route.path.includes('/files') || route.path.includes('/settings')
)

const currentStatusText = computed(() => {
    const p = auth.user?.preferences
    if (!p) return 'Active'
    if (p.status_emoji && p.status_text) return `${p.status_emoji} ${p.status_text}`
    const labels = { online: 'Active', away: 'Away', dnd: 'Do Not Disturb', offline: 'Offline' }
    return labels[p.status] ?? 'Active'
})

const userStatusDot = computed(() => {
    const s = auth.user?.preferences?.status ?? 'online'
    return {
        online:  'bg-green-500',
        away:    'bg-yellow-400',
        dnd:     'bg-red-500',
        offline: 'bg-gray-600',
    }[s] ?? 'bg-green-500'
})

function handleKeydown(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        showSearch.value = true
    }
    if (e.key === 'Escape') {
        showSearch.value = false
        showCreateRoom.value = false
        showChannelBrowser.value = false
        showUserMenu.value = false
    }
}

onMounted(async () => {
    await auth.fetchUser()
    await workspaceStore.loadFromRoute(route.params.workspaceSlug)
    await chat.fetchRooms()
    document.addEventListener('keydown', handleKeydown)

    // If URL has a channel, select it
    if (route.params.channelId) {
        chat.selectRoom(parseInt(route.params.channelId))
    }
})

onBeforeUnmount(() => document.removeEventListener('keydown', handleKeydown))

function selectChannel(roomId) {
    showChannelBrowser.value = false
    chat.selectRoom(roomId)
    const base = route.path.split('/c/')[0].split('/channels')[0]
    router.push(`${base}/c/${roomId}`)
}

async function joinChannel(roomId) {
    await chat.joinRoom(roomId)
    selectChannel(roomId)
}

async function onRoomCreated(room) {
    showCreateRoom.value = false
    await chat.fetchRooms()
    selectChannel(room.id)
}

function openThread(message) {
    activeThread.value = message
}
</script>
