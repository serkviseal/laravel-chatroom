<template>
  <div class="px-4 py-2 space-y-1">
    <MessageItem
      v-for="msg in messages"
      :key="msg.id"
      :message="msg"
      @react="(emoji) => chat.reactToMessage(msg.id, emoji)"
      @edit="chat.editMessage(msg.id, $event)"
      @delete="chat.deleteMessage(msg.id)"
      @open-thread="$emit('open-thread', $event)"
    />
  </div>
</template>

<script setup>
import { useChatStore } from '@/stores/chat'
import MessageItem from './MessageItem.vue'

defineProps({
    messages: { type: Array, default: () => [] },
    roomId: { type: Number, default: null },
})

defineEmits(['open-thread'])

const chat = useChatStore()
</script>
