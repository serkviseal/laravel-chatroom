<template>
  <div class="flex h-full">
    <!-- Chat pane -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Header -->
      <header class="flex items-center gap-3 px-4 py-3 border-b border-gray-800 bg-gray-900 flex-shrink-0">
        <span class="text-gray-500">#</span>
        <h2 class="font-semibold text-white">{{ room?.name }}</h2>
        <span class="text-xs text-gray-500 ml-1">{{ room?.description }}</span>
      </header>

      <!-- Messages -->
      <MessageList
        :messages="chat.activeMessages"
        :room-id="room?.id"
        class="flex-1 overflow-y-auto"
        ref="listRef"
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
import { ref, watch, nextTick } from 'vue'
import { useChatStore } from '@/stores/chat'
import MessageList from './MessageList.vue'
import MessageInput from './MessageInput.vue'
import TypingIndicator from './TypingIndicator.vue'
import OnlineUsers from './OnlineUsers.vue'

const props = defineProps({
  room: { type: Object, default: null },
})

const chat = useChatStore()
const listRef = ref(null)

watch(() => chat.activeMessages.length, async () => {
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
