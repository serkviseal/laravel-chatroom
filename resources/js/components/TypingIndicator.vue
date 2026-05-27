<template>
  <div class="px-4 py-1 h-6 text-xs text-gray-500 italic">
    <span v-if="typingText">{{ typingText }}</span>
  </div>
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

const typingText = computed(() => {
  if (!props.roomId) return ''
  const users = chat.typingUsers[props.roomId] ?? {}
  const names = Object.values(users).filter(name => name !== auth.user?.name)
  if (names.length === 0) return ''
  if (names.length === 1) return `${names[0]} is typing...`
  return `${names.slice(0, -1).join(', ')} and ${names[names.length - 1]} are typing...`
})
</script>
