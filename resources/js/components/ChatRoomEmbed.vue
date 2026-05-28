<template>
  <div class="flex flex-col h-full">
    <!-- Messages -->
    <div ref="scrollEl" class="flex-1 overflow-y-auto p-4 space-y-1">
      <MessageItem
        v-for="(msg, i) in messages"
        :key="msg.id"
        :message="msg"
        :isGrouped="isGrouped(i)"
        :showDate="showDateSep(i)"
      />
      <div ref="bottomEl"></div>
    </div>

    <!-- Window expired warning -->
    <div v-if="windowExpired" class="px-4 py-2 bg-yellow-900/30 border-t border-yellow-700/50 flex items-center gap-2">
      <svg class="w-4 h-4 text-yellow-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <p class="text-xs text-yellow-300">24-hour messaging window expired. Use a template to re-engage.</p>
      <button @click="$emit('openTemplatePicker')" class="ml-auto text-xs bg-yellow-700/50 hover:bg-yellow-700 text-yellow-200 px-2 py-0.5 rounded">
        Send Template
      </button>
    </div>

    <!-- AI Suggestions -->
    <AISuggest v-if="conversationId" :conversationId="conversationId" @use="insertSuggestion" />

    <!-- Input -->
    <div class="border-t border-gray-700 p-3">
      <div class="flex gap-2">
        <textarea
          v-model="draft"
          @keydown.enter.exact.prevent="send"
          :disabled="windowExpired"
          rows="1"
          :placeholder="windowExpired ? 'Window expired — use a template' : 'Reply to customer…'"
          class="flex-1 bg-gray-800 border border-gray-600 rounded-xl text-gray-200 text-sm px-3 py-2 resize-none focus:outline-none focus:ring-1 focus:ring-brand-500 disabled:opacity-50"
        ></textarea>
        <button
          @click="send"
          :disabled="!draft.trim() || windowExpired"
          class="bg-brand-600 hover:bg-brand-700 disabled:opacity-40 text-white rounded-xl px-4 py-2 text-sm font-medium transition-colors"
        >Send</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import axios from 'axios'
import { useChatStore } from '../stores/chat.js'
import MessageItem from './MessageItem.vue'
import AISuggest from './AISuggest.vue'

const props = defineProps({
  roomId:         { type: Number, required: true },
  conversationId: { type: Number, default: null },
  windowExpiresAt: { type: String, default: null },
})
const emit = defineEmits(['openTemplatePicker'])

const chat      = useChatStore()
const messages  = computed(() => chat.messages(props.roomId))
const draft     = ref('')
const scrollEl  = ref(null)
const bottomEl  = ref(null)

const windowExpired = computed(() => {
  if (!props.windowExpiresAt) return false
  return new Date(props.windowExpiresAt) < new Date()
})

onMounted(async () => {
  await chat.joinRoom(props.roomId)
  scrollToBottom()
})

watch(messages, () => nextTick(scrollToBottom), { deep: true })

async function send() {
  const body = draft.value.trim()
  if (!body) return
  draft.value = ''
  await chat.sendMessage(props.roomId, body)
}

function insertSuggestion(text) {
  draft.value = text
}

function scrollToBottom() {
  bottomEl.value?.scrollIntoView({ behavior: 'smooth' })
}

function isGrouped(i) {
  if (i === 0) return false
  const cur  = messages.value[i]
  const prev = messages.value[i - 1]
  if (cur.user_id !== prev.user_id || cur.origin !== prev.origin) return false
  return new Date(cur.created_at) - new Date(prev.created_at) < 2 * 60 * 1000
}

function showDateSep(i) {
  if (i === 0) return true
  const cur  = new Date(messages.value[i]?.created_at).toDateString()
  const prev = new Date(messages.value[i - 1]?.created_at).toDateString()
  return cur !== prev
}
</script>
