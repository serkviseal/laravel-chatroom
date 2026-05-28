<template>
  <div class="relative">
    <button
      @click="toggle"
      class="relative p-1 text-gray-400 hover:text-white transition-colors"
      title="Notifications"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <span
        v-if="store.unreadCount > 0"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold"
      >{{ store.unreadCount > 9 ? '9+' : store.unreadCount }}</span>
    </button>

    <div
      v-if="open"
      class="absolute right-0 top-8 w-80 bg-gray-800 border border-gray-700 rounded-xl shadow-2xl z-50 overflow-hidden"
    >
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
        <span class="font-semibold text-sm">Notifications</span>
        <button @click="store.markRead()" class="text-xs text-indigo-400 hover:text-indigo-300">Mark all read</button>
      </div>
      <div class="max-h-96 overflow-y-auto">
        <div v-if="store.notifications.length === 0" class="p-4 text-center text-gray-500 text-sm">No notifications</div>
        <div
          v-for="n in store.notifications"
          :key="n.id"
          @click="store.markRead(n.id)"
          class="px-4 py-3 border-b border-gray-700 last:border-0 cursor-pointer transition"
          :class="n.read_at ? 'opacity-60' : 'bg-gray-750 hover:bg-gray-700'"
        >
          <div class="flex items-start gap-2">
            <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="n.read_at ? 'bg-gray-600' : 'bg-indigo-500'" />
            <div>
              <div class="text-sm text-white">{{ formatNotification(n) }}</div>
              <div class="text-xs text-gray-500 mt-0.5">{{ timeAgo(n.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notifications'

const store = useNotificationStore()
const open = ref(false)

onMounted(() => store.fetchNotifications())

function toggle() {
    open.value = !open.value
    if (open.value) store.fetchNotifications()
}

function formatNotification(n) {
    const d = typeof n.data === 'string' ? JSON.parse(n.data) : n.data
    if (d?.type === 'mention') return `${d.sender_name} mentioned you: "${d.preview}"`
    if (d?.type === 'direct_message') return `${d.sender_name}: "${d.preview}"`
    return 'New notification'
}

function timeAgo(dateStr) {
    const diff = Date.now() - new Date(dateStr).getTime()
    const mins = Math.floor(diff / 60000)
    if (mins < 1) return 'just now'
    if (mins < 60) return `${mins}m ago`
    const hrs = Math.floor(mins / 60)
    if (hrs < 24) return `${hrs}h ago`
    return `${Math.floor(hrs / 24)}d ago`
}
</script>
