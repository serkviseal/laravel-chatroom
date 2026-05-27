<template>
  <div class="flex h-full bg-gray-950 text-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 flex flex-col bg-gray-900 border-r border-gray-800">
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800">
        <span class="font-bold text-brand-400 tracking-tight">💬 Chatroom</span>
        <button @click="auth.logout()" class="text-xs text-gray-500 hover:text-gray-300 transition-colors">
          Sign out
        </button>
      </div>

      <RoomList
        :rooms="chat.rooms"
        :active-room-id="chat.activeRoomId"
        @select="chat.selectRoom"
        @join="chat.joinRoom"
        @create="showCreateRoom = true"
      />

      <div class="mt-auto p-3 border-t border-gray-800 flex items-center gap-2">
        <img :src="auth.user?.avatar_url" class="w-7 h-7 rounded-full object-cover" :alt="auth.user?.name" />
        <span class="text-sm text-gray-400 truncate">{{ auth.user?.name }}</span>
      </div>
    </aside>

    <!-- Main area -->
    <main class="flex-1 flex flex-col min-w-0">
      <ChatRoom v-if="chat.activeRoomId" :room="chat.activeRoom" />
      <div v-else class="flex-1 flex items-center justify-center">
        <div class="text-center text-gray-500">
          <div class="text-5xl mb-4">💬</div>
          <p class="text-lg font-medium">Select a room to start chatting</p>
        </div>
      </div>
    </main>

    <!-- Create room modal -->
    <CreateRoomModal v-if="showCreateRoom" @close="showCreateRoom = false" @created="onRoomCreated" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useChatStore } from '@/stores/chat'
import RoomList from './RoomList.vue'
import ChatRoom from './ChatRoom.vue'
import CreateRoomModal from './CreateRoomModal.vue'

const auth = useAuthStore()
const chat = useChatStore()
const showCreateRoom = ref(false)

onMounted(async () => {
  await auth.fetchUser()
  await chat.fetchRooms()
})

async function onRoomCreated(room) {
  showCreateRoom.value = false
  await chat.fetchRooms()
  await chat.selectRoom(room.id)
}
</script>
