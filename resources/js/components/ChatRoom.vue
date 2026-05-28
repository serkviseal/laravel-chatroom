<template>
  <div class="flex h-full">
    <!-- Chat pane -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Header -->
      <header class="flex items-center gap-3 px-4 py-3 border-b border-gray-800 bg-gray-900 flex-shrink-0">
        <span class="text-gray-500">#</span>
        <h2 class="font-semibold text-white">{{ room?.name }}</h2>
        <span v-if="room?.topic" class="text-xs text-gray-500 border-l border-gray-700 pl-3 ml-1">{{ room.topic }}</span>
        <div class="ml-auto flex items-center gap-3">
          <span v-if="room?.type" class="text-xs text-gray-600 bg-gray-800 px-2 py-0.5 rounded capitalize">{{ room.type }}</span>
          <button @click="showPinned = !showPinned" class="text-gray-500 hover:text-white text-sm" title="Pinned messages">📌</button>
        </div>
      </header>

      <!-- Pinned messages bar -->
      <div v-if="showPinned && pinnedMessages.length" class="px-4 py-2 bg-yellow-900/20 border-b border-yellow-800/30">
        <div class="text-xs text-yellow-500 font-medium mb-1">Pinned Messages</div>
        <div v-for="m in pinnedMessages" :key="m.id" class="text-sm text-gray-300 truncate">
          <span class="text-gray-500">{{ m.user?.name }}:</span> {{ m.body }}
        </div>
      </div>

      <!-- Messages -->
      <MessageList
        :messages="chat.activeMessages"
        :room-id="room?.id"
        class="flex-1 overflow-y-auto"
        ref="listRef"
        @open-thread="$emit('open-thread', $event)"
      />

      <!-- Typing indicator -->
      <TypingIndicator :room-id="room?.id" />

      <!-- Input -->
      <MessageInput :room="room" @sent="scrollBottom" />
    </div>

    <!-- Online users sidebar -->
    <OnlineUsers :room-id="room?.id" />
  </div>
</template>

<script setup>
import { ref, watch, computed, nextTick } from 'vue'
import { useChatStore } from '@/stores/chat'
import MessageList from './MessageList.vue'
import MessageInput from './MessageInput.vue'
import TypingIndicator from './TypingIndicator.vue'
import OnlineUsers from './OnlineUsers.vue'

const props = defineProps({
    room: { type: Object, default: null },
})

defineEmits(['open-thread'])

const chat = useChatStore()
const listRef = ref(null)
const showPinned = ref(false)

const pinnedMessages = computed(() =>
    (chat.activeMessages || []).filter(m => m.is_pinned)
)

watch(() => chat.activeMessages?.length, async () => {
    await nextTick()
    scrollBottom()
})

function scrollBottom() {
    if (listRef.value?.$el) {
        const el = listRef.value.$el
        el.scrollTop = el.scrollHeight
    }
}
</script>
