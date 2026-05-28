<template>
  <aside class="w-80 flex-shrink-0 flex flex-col bg-gray-900 border-l border-gray-800">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800">
      <span class="font-semibold text-sm">Thread</span>
      <button @click="$emit('close')" class="text-gray-500 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Root message -->
    <div class="px-4 py-3 border-b border-gray-800 bg-gray-800/50">
      <div class="flex items-start gap-2">
        <img :src="message.user?.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
        <div>
          <div class="flex items-baseline gap-2">
            <span class="font-semibold text-sm text-white">{{ message.user?.name }}</span>
            <span class="text-xs text-gray-500">{{ formatTime(message.created_at) }}</span>
          </div>
          <div class="text-sm text-gray-200 mt-1" v-html="message.body_html || message.body" />
        </div>
      </div>
    </div>

    <!-- Replies -->
    <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3">
      <div v-if="loading" class="text-center text-gray-500 text-sm">Loading...</div>
      <div
        v-for="reply in replies"
        :key="reply.id"
        class="flex items-start gap-2"
      >
        <img :src="reply.user?.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0 mt-0.5" />
        <div>
          <div class="flex items-baseline gap-2">
            <span class="font-semibold text-sm text-white">{{ reply.user?.name }}</span>
            <span class="text-xs text-gray-500">{{ formatTime(reply.created_at) }}</span>
          </div>
          <div class="text-sm text-gray-200 mt-0.5" v-html="reply.body_html || reply.body" />
        </div>
      </div>
    </div>

    <!-- Reply input -->
    <div class="px-4 py-3 border-t border-gray-800">
      <div class="flex gap-2">
        <textarea
          v-model="replyBody"
          rows="2"
          placeholder="Reply in thread..."
          class="flex-1 bg-gray-800 text-white rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @keydown.enter.exact.prevent="sendReply"
        />
        <button
          @click="sendReply"
          :disabled="!replyBody.trim()"
          class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white rounded-lg px-3 py-2 text-sm transition-colors"
        >Send</button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({ message: { type: Object, required: true } })
defineEmits(['close'])

const replies = ref([])
const replyBody = ref('')
const loading = ref(false)

onMounted(fetchReplies)

async function fetchReplies() {
    loading.value = true
    try {
        const { data } = await axios.get(`/api/v1/rooms/${props.message.room_id}/messages/${props.message.id}/replies`)
        replies.value = data.data
    } finally {
        loading.value = false
    }
}

async function sendReply() {
    if (!replyBody.value.trim()) return
    const body = replyBody.value.trim()
    replyBody.value = ''
    const { data } = await axios.post(
        `/api/v1/rooms/${props.message.room_id}/messages/${props.message.id}/replies`,
        { body }
    )
    replies.value.push(data.data)
}

function formatTime(iso) {
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>
