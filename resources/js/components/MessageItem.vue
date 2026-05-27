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
        <!-- Text -->
        <span v-else class="whitespace-pre-wrap break-words">{{ message.body }}</span>

        <!-- Edited badge -->
        <span v-if="message.edited_at && !message.deleted_at" class="text-xs opacity-50 ml-2">
          (edited)
        </span>

        <!-- Hover actions -->
        <div
          v-if="!message.deleted_at"
          class="absolute top-1 hidden group-hover:flex items-center gap-1 bg-gray-900 border border-gray-700 rounded-lg px-1.5 py-0.5 shadow"
          :class="message.is_mine ? '-left-20' : '-right-20'"
        >
          <button
            v-for="emoji in quickReactions"
            :key="emoji"
            @click="$emit('react', emoji)"
            class="text-sm hover:scale-125 transition-transform"
          >{{ emoji }}</button>
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
      class="w-full bg-gray-800 border border-brand-500 rounded px-3 py-1.5 text-sm text-white focus:outline-none"
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

const emit = defineEmits(['react', 'edit', 'delete'])

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

function formatTime(iso) {
  return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>
