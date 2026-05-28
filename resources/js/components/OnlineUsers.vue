<template>
  <aside class="w-52 flex-shrink-0 bg-gray-900 border-l border-gray-800 flex flex-col overflow-hidden">
    <div class="px-3 py-2.5 border-b border-gray-800 flex items-center justify-between">
      <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Members</h3>
      <span class="text-xs text-gray-600">{{ onlineCount }} online</span>
    </div>

    <div class="flex-1 overflow-y-auto">
      <!-- Online section -->
      <div v-if="online.length" class="px-3 pt-3 pb-1">
        <p class="text-xs text-gray-600 font-medium uppercase tracking-wider mb-1.5">
          Online — {{ online.length }}
        </p>
        <ul class="space-y-1">
          <li
            v-for="user in online"
            :key="user.id"
            class="flex items-center gap-2 py-1 px-1 rounded-lg hover:bg-gray-800 cursor-pointer transition-colors group"
            :title="user.name"
          >
            <div class="relative flex-shrink-0">
              <img
                :src="user.avatar_url"
                :alt="user.name"
                class="w-7 h-7 rounded-full object-cover"
              />
              <span
                class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-gray-900"
                :class="statusDot(user.status)"
              />
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-xs text-gray-200 truncate leading-tight">
                {{ user.name }}
                <span v-if="isMe(user)" class="text-gray-600 font-normal"> (you)</span>
              </div>
              <div v-if="user.status_emoji || user.status_text" class="text-xs text-gray-500 truncate">
                {{ user.status_emoji }} {{ user.status_text }}
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Offline / away section -->
      <div v-if="offline.length" class="px-3 pt-2 pb-3">
        <p class="text-xs text-gray-700 font-medium uppercase tracking-wider mb-1.5">
          Offline — {{ offline.length }}
        </p>
        <ul class="space-y-1">
          <li
            v-for="user in offline"
            :key="user.id"
            class="flex items-center gap-2 py-1 px-1 rounded-lg hover:bg-gray-800 cursor-pointer transition-colors opacity-50"
          >
            <div class="relative flex-shrink-0">
              <img :src="user.avatar_url" :alt="user.name" class="w-7 h-7 rounded-full object-cover grayscale" />
              <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-gray-900 status-offline" />
            </div>
            <span class="text-xs text-gray-500 truncate">{{ user.name }}</span>
          </li>
        </ul>
      </div>

      <div v-if="!online.length && !offline.length" class="flex flex-col items-center justify-center py-8 text-center px-4">
        <div class="text-2xl mb-2">👤</div>
        <p class="text-xs text-gray-600">No one in this channel yet</p>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  roomId: { type: Number, default: null },
})

const chat = useChatStore()
const auth = useAuthStore()

const allUsers = computed(() =>
  props.roomId ? (chat.onlineUsers[props.roomId] ?? []) : []
)

const online  = computed(() => allUsers.value.filter(u => !u.status || u.status !== 'offline'))
const offline = computed(() => allUsers.value.filter(u => u.status === 'offline'))
const onlineCount = computed(() => online.value.length)

function isMe(user) { return user.id === auth.user?.id }

function statusDot(status) {
  const map = {
    online:  'status-online',
    away:    'status-away',
    dnd:     'status-dnd',
    offline: 'status-offline',
  }
  return map[status] ?? 'status-online'
}
</script>
