<template>
  <div
    class="group flex gap-2 py-1"
    :class="message.is_mine ? 'flex-row-reverse' : 'flex-row'"
  >
    <!-- Avatar -->
    <img
      :src="message.user?.avatar_url"
      :alt="message.user?.name"
      class="w-7 h-7 rounded-full object-cover flex-shrink-0 self-end"
    />

    <div class="flex flex-col max-w-[70%]" :class="message.is_mine ? 'items-end' : 'items-start'">
      <!-- Username + time -->
      <span v-if="!message.is_mine" class="text-xs text-gray-500 mb-0.5 ml-1">
        {{ message.user?.name }}
      </span>

      <!-- Bubble -->
      <div
        class="relative chat-bubble"
        :class="message.is_mine ? 'chat-bubble-mine' : 'chat-bubble-theirs'"
      >
        <!-- Deleted -->
        <span v-if="message.deleted_at" class="italic text-gray-400 text-xs">Message deleted</span>
        <!-- Attachment -->
        <img
          v-else-if="message.type === 'image' && message.attachment_url"
          :src="message.attachment_url"
          class="rounded-lg max-w-xs"
          alt="attachment"
        />
        <!-- Pinned badge -->
        <span v-if="message.is_pinned" class="text-xs text-yellow-500 block mb-1">📌 Pinned</span>
        <!-- Rendered markdown or plain text -->
        <div
          v-if="!message.deleted_at && message.body"
          class="prose prose-invert prose-sm max-w-none"
          v-html="message.body_html || escapeHtml(message.body)"
        />

        <!-- Edited badge -->
        <span v-if="message.edited_at && !message.deleted_at" class="text-xs opacity-50 ml-2">
          (edited)
        </span>

        <!-- Hover actions -->
        <div
          v-if="!message.deleted_at"
          class="absolute top-1 hidden group-hover:flex items-center gap-1 bg-gray-900 border border-gray-700 rounded-lg px-1.5 py-0.5 shadow"
          :class="message.is_mine ? '-left-28' : '-right-28'"
        >
          <button
            v-for="e in quickReactions"
            :key="e"
            @click="$emit('react', e)"
            class="text-sm hover:scale-125 transition-transform"
          >{{ e }}</button>
          <button
            @click="$emit('open-thread', message)"
            class="text-xs text-gray-400 hover:text-white px-1"
            title="Reply in thread"
          >💬</button>
          <button
            v-if="message.is_mine"
            @click="startEdit"
            class="text-xs text-gray-400 hover:text-white px-1"
          >✏️</button>
          <button
            v-if="message.is_mine"
            @click="$emit('delete')"
            class="text-xs text-gray-400 hover:text-red-400 px-1"
          >🗑</button>
        </div>
      </div>

      <!-- Reactions -->
      <div v-if="message.reactions?.length" class="flex flex-wrap gap-1 mt-1">
        <button
          v-for="r in message.reactions"
          :key="r.emoji"
          @click="$emit('react', r.emoji)"
          class="flex items-center gap-0.5 text-xs bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-full px-2 py-0.5"
        >
          {{ r.emoji }} <span class="text-gray-400">{{ r.count }}</span>
        </button>
      </div>

      <!-- Thread reply count -->
      <button
        v-if="!message.is_thread_reply && message.reply_count > 0"
        @click="$emit('open-thread', message)"
        class="text-xs text-indigo-400 hover:text-indigo-300 mt-0.5 px-1"
      >
        {{ message.reply_count }} {{ message.reply_count === 1 ? 'reply' : 'replies' }} →
      </button>

      <span class="text-xs text-gray-600 mt-0.5 px-1">
        {{ formatTime(message.created_at) }}
      </span>
    </div>
  </div>

  <!-- Edit input -->
  <div v-if="editing" class="px-2 py-1">
    <input
      v-model="editBody"
      @keydown.enter.exact.prevent="submitEdit"
      @keydown.escape="editing = false"
      class="w-full bg-gray-800 border border-indigo-500 rounded px-3 py-1.5 text-sm text-white focus:outline-none"
      autofocus
    />
    <p class="text-xs text-gray-500 mt-1">Enter to save · Esc to cancel</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    message: { type: Object, required: true },
})

const emit = defineEmits(['react', 'edit', 'delete', 'open-thread'])

const quickReactions = ['👍', '❤️', '😂', '😮', '😢']
const editing = ref(false)
const editBody = ref('')

function startEdit() {
    editBody.value = props.message.body
    editing.value = true
}

function submitEdit() {
    if (editBody.value.trim()) {
        emit('edit', editBody.value.trim())
        editing.value = false
    }
}

function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
}

function formatTime(iso) {
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>
