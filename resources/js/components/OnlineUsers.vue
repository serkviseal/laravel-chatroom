<template>
  <aside class="w-48 flex-shrink-0 bg-gray-900 border-l border-gray-800 flex flex-col">
    <div class="px-3 py-2 border-b border-gray-800">
      <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
        Online — {{ users.length }}
      </h3>
    </div>
    <ul class="flex-1 overflow-y-auto px-3 py-2 space-y-2">
      <li
        v-for="user in users"
        :key="user.id"
        class="flex items-center gap-2"
      >
        <div class="relative flex-shrink-0">
          <img :src="user.avatar_url" :alt="user.name" class="w-7 h-7 rounded-full object-cover" />
          <span class="absolute bottom-0 right-0 w-2 h-2 bg-green-500 rounded-full border border-gray-900"></span>
        </div>
        <span class="text-sm text-gray-300 truncate">{{ user.name }}</span>
      </li>
      <li v-if="!users.length" class="text-xs text-gray-600 text-center py-2">
        No one online
      </li>
    </ul>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useChatStore } from '@/stores/chat'

const props = defineProps({
  roomId: { type: Number, default: null },
})

const chat = useChatStore()

const users = computed(() =>
  props.roomId ? (chat.onlineUsers[props.roomId] ?? []) : []
)
</script>
