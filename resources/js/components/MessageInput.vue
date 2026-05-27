<template>
  <form
    @submit.prevent="send"
    class="flex items-end gap-2 px-4 py-3 border-t border-gray-800 bg-gray-900 flex-shrink-0"
  >
    <textarea
      v-model="body"
      @keydown.enter.exact.prevent="send"
      @input="onTyping"
      placeholder="Message #{{ room?.name }}"
      rows="1"
      class="flex-1 bg-gray-800 text-sm text-gray-100 placeholder-gray-600 rounded-xl px-4 py-2.5 border border-gray-700 focus:outline-none focus:border-brand-500 resize-none max-h-32"
    />

    <!-- File attach -->
    <label class="cursor-pointer text-gray-500 hover:text-gray-300 transition-colors p-2">
      📎
      <input type="file" class="hidden" @change="attachFile" accept="image/*,.pdf,.doc,.docx" />
    </label>

    <button
      type="submit"
      :disabled="!body.trim() && !attachment"
      class="bg-brand-600 hover:bg-brand-700 disabled:opacity-40 text-white rounded-xl px-4 py-2.5 text-sm font-medium transition-colors flex-shrink-0"
    >
      Send
    </button>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useChatStore } from '@/stores/chat'
import axios from 'axios'

const props = defineProps({
  room: { type: Object, default: null },
})

const emit = defineEmits(['sent'])

const auth = useAuthStore()
const chat = useChatStore()

const body = ref('')
const attachment = ref(null)
let typingTimeout = null

function onTyping() {
  if (!props.room?.id || !auth.user) return
  clearTimeout(typingTimeout)
  chat.whisperTyping(props.room.id, { id: auth.user.id, name: auth.user.name })
  typingTimeout = setTimeout(() => {}, 2000)
}

function attachFile(e) {
  attachment.value = e.target.files[0] ?? null
}

async function send() {
  if (!props.room?.id) return

  if (attachment.value) {
    const form = new FormData()
    form.append('room_id', props.room.id)
    if (body.value.trim()) form.append('body', body.value.trim())
    form.append('attachment', attachment.value)
    const { data } = await axios.post('/api/v1/messages', form)
    chat.activeMessages.push(data.data)
    attachment.value = null
  } else if (body.value.trim()) {
    await chat.sendMessage(body.value.trim(), props.room.id)
  }

  body.value = ''
  emit('sent')
}
</script>
