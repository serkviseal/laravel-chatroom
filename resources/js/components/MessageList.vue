<template>
  <div ref="listEl" class="flex-1 overflow-y-auto overflow-x-hidden py-2">
    <div v-if="!messages.length" class="flex flex-col items-center justify-center h-full text-center px-8">
      <div class="text-5xl mb-3">👋</div>
      <p class="text-gray-400 font-medium">This is the beginning of the conversation.</p>
      <p class="text-gray-600 text-sm mt-1">Be the first to say something!</p>
    </div>

    <MessageItem
      v-for="(msg, i) in messages"
      :key="msg.id"
      :message="msg"
      :is-grouped="isGrouped(i)"
      :show-date="showDateSep(i)"
      @react="(emoji) => chat.reactToMessage(msg.id, emoji)"
      @edit="chat.editMessage(msg.id, $event)"
      @delete="chat.deleteMessage(msg.id)"
      @open-thread="$emit('open-thread', $event)"
    />

    <!-- Bottom anchor for auto-scroll -->
    <div ref="bottomEl" class="h-1" />
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted } from 'vue'
import { useChatStore } from '@/stores/chat'
import MessageItem from './MessageItem.vue'

const props = defineProps({
  messages: { type: Array, default: () => [] },
})

defineEmits(['open-thread'])

const chat   = useChatStore()
const listEl = ref(null)
const bottomEl = ref(null)

// ── Grouping: same sender within 2 minutes ───────────────────────
function isGrouped(i) {
  if (i === 0) return false
  const cur  = props.messages[i]
  const prev = props.messages[i - 1]
  if (!cur || !prev) return false
  if (cur.user_id !== prev.user_id) return false
  return new Date(cur.created_at) - new Date(prev.created_at) < 2 * 60 * 1000
}

// ── Date separator: show when day changes ────────────────────────
function showDateSep(i) {
  if (i === 0) return true
  const cur  = new Date(props.messages[i]?.created_at).toDateString()
  const prev = new Date(props.messages[i - 1]?.created_at).toDateString()
  return cur !== prev
}

// ── Auto-scroll to bottom when new messages arrive ───────────────
function scrollToBottom(smooth = false) {
  nextTick(() => {
    bottomEl.value?.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant' })
  })
}

watch(() => props.messages.length, (newLen, oldLen) => {
  // smooth scroll only when a single new message arrives (not bulk load)
  scrollToBottom(newLen - oldLen === 1)
})

onMounted(() => scrollToBottom())

defineExpose({ scrollToBottom })
</script>
