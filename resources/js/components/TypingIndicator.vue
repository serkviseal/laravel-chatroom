<template>
  <transition name="slide-up">
    <div
      v-if="typingNames.length"
      class="flex items-center gap-2 px-4 py-1.5 text-xs text-gray-400"
    >
      <!-- Mini avatars of typers (max 3) -->
      <div class="flex -space-x-1">
        <img
          v-for="u in typingUsers.slice(0, 3)"
          :key="u.id"
          :src="u.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(u.name)}&background=6366f1&color=fff&size=32`"
          :alt="u.name"
          class="w-5 h-5 rounded-full ring-1 ring-gray-900 object-cover"
        />
      </div>

      <!-- Animated dots -->
      <span class="flex items-center gap-0.5 h-4">
        <span class="typing-dot" />
        <span class="typing-dot" />
        <span class="typing-dot" />
      </span>

      <!-- Text -->
      <span>
        <span v-if="typingNames.length === 1">
          <strong class="text-gray-300">{{ typingNames[0] }}</strong> is typing…
        </span>
        <span v-else-if="typingNames.length <= 3">
          <strong class="text-gray-300">{{ typingNames.slice(0, -1).join(', ') }}</strong>
          and <strong class="text-gray-300">{{ typingNames[typingNames.length - 1] }}</strong> are typing…
        </span>
        <span v-else>
          Several people are typing…
        </span>
      </span>
    </div>
  </transition>
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

const typingUsers = computed(() => {
  if (!props.roomId) return []
  const map = chat.typingUsers[props.roomId] ?? {}
  return Object.values(map).filter(u => u.id !== auth.user?.id)
})

const typingNames = computed(() => typingUsers.value.map(u => u.name))
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(4px); }
</style>
