<template>
  <div class="flex h-screen bg-gray-950 text-gray-100 overflow-hidden">

    <!-- Workspace Sidebar -->
    <aside class="w-64 flex-shrink-0 flex flex-col bg-gray-900 border-r border-gray-800">
      <!-- Workspace Header -->
      <div class="px-4 py-3 border-b border-gray-800 flex items-center justify-between">
        <div class="flex items-center gap-2 min-w-0">
          <img
            v-if="workspaceStore.currentWorkspace"
            :src="workspaceStore.currentWorkspace.avatar_url"
            class="w-7 h-7 rounded-md object-cover flex-shrink-0"
          />
          <span class="font-bold text-indigo-400 tracking-tight truncate">
            {{ workspaceStore.currentWorkspace?.name ?? 'Chatroom' }}
          </span>
        </div>
        <div class="flex items-center gap-2">
          <NotificationBell />
          <button @click="router.push('/home')" class="text-xs text-gray-500 hover:text-gray-300 transition-colors" title="Switch workspace">⇄</button>
        </div>
      </div>

      <!-- Channel list -->
      <RoomList
        :rooms="chat.rooms"
        :active-room-id="chat.activeRoomId"
        @select="selectChannel"
        @join="chat.joinRoom"
        @create="showCreateRoom = true"
        @browse="showChannelBrowser = true"
      />

      <!-- User footer -->
      <div class="mt-auto p-3 border-t border-gray-800 flex items-center gap-2 cursor-pointer hover:bg-gray-800 rounded-lg" @click="showUserMenu = !showUserMenu">
        <img :src="auth.user?.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0" :alt="auth.user?.name" />
        <div class="flex-1 min-w-0">
          <div class="text-sm text-gray-200 truncate">{{ auth.user?.name }}</div>
          <div class="text-xs text-gray-500">{{ currentStatus }}</div>
        </div>
      </div>
      <UserStatusMenu v-if="showUserMenu" @close="showUserMenu = false" />
    </aside>

    <!-- Main area with optional thread panel -->
    <div class="flex flex-1 min-w-0">
      <!-- Channel view or router-view for sub-pages -->
      <main class="flex-1 flex flex-col min-w-0">
        <router-view v-if="isSubPage" />
        <template v-else>
          <ChatRoom
            v-if="chat.activeRoomId"
            :room="chat.activeRoom"
            @open-thread="openThread"
          />
          <div v-else class="flex-1 flex items-center justify-center">
            <div class="text-center text-gray-600">
              <div class="text-6xl mb-4">💬</div>
              <p class="text-lg font-medium text-gray-500">Select a channel to start chatting</p>
              <button @click="showChannelBrowser = true" class="mt-4 text-indigo-400 hover:text-indigo-300 text-sm">Browse channels</button>
            </div>
          </div>
        </template>
      </main>

      <!-- Thread panel -->
      <ThreadPanel v-if="activeThread" :message="activeThread" @close="activeThread = null" />
    </div>

    <!-- Modals -->
    <CreateRoomModal v-if="showCreateRoom" @close="showCreateRoom = false" @created="onRoomCreated" />
    <ChannelBrowser v-if="showChannelBrowser" @close="showChannelBrowser = false" @select="selectChannel" />
    <SearchModal v-if="showSearch" @close="showSearch = false" />
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

const auth = useAuthStore()
const chat = useChatStore()
const workspaceStore = useWorkspaceStore()
const router = useRouter()
const route = useRoute()

const showCreateRoom = ref(false)
const showChannelBrowser = ref(false)
const showUserMenu = ref(false)
const showSearch = ref(false)
const activeThread = ref(null)

const isSubPage = computed(() =>
    route.path.includes('/files') || route.path.includes('/settings')
)

const currentStatus = computed(() => {
    const pref = auth.user?.preferences
    return pref?.status_emoji ? `${pref.status_emoji} ${pref.status_text || pref.status}` : (pref?.status ?? 'Active')
})

// Cmd+K global search shortcut
function handleKeydown(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        showSearch.value = true
    }
}

onMounted(async () => {
    await auth.fetchUser()
    await chat.fetchRooms()
    document.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown)
})

function selectChannel(roomId) {
    showChannelBrowser.value = false
    chat.selectRoom(roomId)
    router.push(`${route.path.split('/c/')[0]}/c/${roomId}`)
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
