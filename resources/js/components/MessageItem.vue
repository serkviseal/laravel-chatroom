<template>
  <!-- Date separator -->
  <div v-if="showDate" class="flex items-center gap-3 px-4 my-4">
    <div class="flex-1 h-px bg-gray-800" />
    <span class="text-xs text-gray-500 font-medium px-2">{{ dateLabel }}</span>
    <div class="flex-1 h-px bg-gray-800" />
  </div>

  <!-- Message row -->
  <div
    class="group relative flex gap-2 px-4"
    :class="[
      message.is_mine ? 'flex-row-reverse' : 'flex-row',
      isGrouped ? 'py-0.5' : 'pt-3 pb-0.5',
    ]"
  >
    <!-- Avatar column (only for others, only when not grouped) -->
    <div class="w-8 flex-shrink-0">
      <img
        v-if="!message.is_mine && !isGrouped"
        :src="message.user?.avatar_url"
        :alt="message.user?.name"
        class="w-8 h-8 rounded-full object-cover ring-1 ring-gray-700"
      />
    </div>

    <!-- Content column -->
    <div class="flex flex-col min-w-0" :class="message.is_mine ? 'items-end' : 'items-start'">

      <!-- Sender + timestamp (first in group only) -->
      <div
        v-if="!isGrouped"
        class="flex items-baseline gap-2 mb-1"
        :class="message.is_mine ? 'flex-row-reverse' : 'flex-row'"
      >
        <span v-if="!message.is_mine" class="text-xs font-semibold text-gray-200">
          {{ message.user?.name }}
        </span>
        <span class="text-xs text-gray-600">{{ formatTime(message.created_at) }}</span>
      </div>

      <!-- Bubble + hover time for grouped -->
      <div class="relative flex items-end gap-1.5" :class="message.is_mine ? 'flex-row-reverse' : 'flex-row'">

        <!-- Bubble -->
        <div
          class="relative"
          :class="[
            message.is_mine ? 'msg-mine' : 'msg-theirs',
            isGrouped ? 'grouped' : '',
            message.deleted_at ? 'opacity-50' : '',
          ]"
        >
          <!-- Deleted -->
          <span v-if="message.deleted_at" class="italic text-xs opacity-60 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
            Message deleted
          </span>

          <!-- Image attachment -->
          <img
            v-else-if="message.type === 'image' && message.attachment_url"
            :src="message.attachment_url"
            class="rounded-xl max-w-xs max-h-48 object-cover block"
            alt="attachment"
          />

          <!-- Inline edit mode -->
          <div v-else-if="editing">
            <textarea
              v-model="editBody"
              @keydown.enter.exact.prevent="submitEdit"
              @keydown.escape="editing = false"
              rows="2"
              class="w-full bg-black/20 rounded-lg px-2 py-1 text-sm focus:outline-none resize-none min-w-[200px]"
              autofocus
            />
            <p class="text-xs opacity-60 mt-1">⏎ save · Esc cancel</p>
          </div>

          <!-- Rendered markdown / text -->
          <div
            v-else-if="message.body"
            class="prose-chat"
            :class="message.is_mine ? 'prose-invert' : 'prose-invert'"
            v-html="message.body_html || escapeHtml(message.body)"
          />

          <!-- Edited badge -->
          <span
            v-if="message.edited_at && !message.deleted_at && !editing"
            class="text-xs opacity-40 ml-1"
          >(edited)</span>
        </div>

        <!-- Hover timestamp for grouped messages -->
        <span
          v-if="isGrouped"
          class="text-xs text-gray-700 opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex-shrink-0 self-center"
        >{{ formatTime(message.created_at) }}</span>
      </div>

      <!-- Reactions row -->
      <div v-if="message.reactions?.length" class="flex flex-wrap gap-1 mt-1.5">
        <button
          v-for="r in message.reactions"
          :key="r.emoji"
          @click="$emit('react', r.emoji)"
          class="flex items-center gap-1 text-xs bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-full px-2 py-0.5 transition-colors"
          :class="r.user_ids?.includes(currentUserId) ? 'border-brand-500/60 bg-brand-900/30' : ''"
        >
          {{ r.emoji }}<span class="text-gray-400">{{ r.count }}</span>
        </button>
      </div>

      <!-- Thread reply count -->
      <button
        v-if="!message.is_thread_reply && message.reply_count > 0"
        @click="$emit('open-thread', message)"
        class="flex items-center gap-1.5 text-xs text-brand-400 hover:text-brand-300 mt-1 px-0.5 transition-colors"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        {{ message.reply_count }} {{ message.reply_count === 1 ? 'reply' : 'replies' }}
      </button>
    </div>

    <!-- Floating action bar (appears on hover) -->
    <div
      v-if="!message.deleted_at"
      class="absolute top-0 hidden group-hover:flex items-center gap-0.5
             bg-gray-900 border border-gray-700 rounded-xl px-1.5 py-1 shadow-xl z-10"
      :class="message.is_mine ? 'left-4' : 'right-4'"
    >
      <!-- Quick reactions -->
      <button
        v-for="e in quickReactions"
        :key="e"
        @click="$emit('react', e)"
        class="text-base w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-800 transition-colors hover:scale-110"
        :title="e"
      >{{ e }}</button>
      <div class="w-px h-4 bg-gray-700 mx-0.5" />
      <!-- Reply in thread -->
      <button
        @click="$emit('open-thread', message)"
        class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors"
        title="Reply in thread"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
        </svg>
      </button>
      <!-- Edit (mine only) -->
      <button
        v-if="message.is_mine && !editing"
        @click="startEdit"
        class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors"
        title="Edit"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
      </button>
      <!-- Delete (mine only) -->
      <button
        v-if="message.is_mine"
        @click="$emit('delete')"
        class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-800 text-gray-400 hover:text-red-400 transition-colors"
        title="Delete"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  message:   { type: Object,  required: true },
  isGrouped: { type: Boolean, default: false },
  showDate:  { type: Boolean, default: false },
})

const emit = defineEmits(['react', 'edit', 'delete', 'open-thread'])

const auth = useAuthStore()
const quickReactions = ['👍', '❤️', '😂', '😮', '😢']
const editing  = ref(false)
const editBody = ref('')

const currentUserId = computed(() => auth.user?.id)

const dateLabel = computed(() => {
  const d = new Date(props.message.created_at)
  const today = new Date()
  const yesterday = new Date(today); yesterday.setDate(today.getDate() - 1)
  if (d.toDateString() === today.toDateString())     return 'Today'
  if (d.toDateString() === yesterday.toDateString()) return 'Yesterday'
  return d.toLocaleDateString([], { month: 'long', day: 'numeric', year: 'numeric' })
})

function startEdit() {
  editBody.value = props.message.body
  editing.value  = true
}

function submitEdit() {
  if (editBody.value.trim()) {
    emit('edit', editBody.value.trim())
    editing.value = false
  }
}

function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
}

function formatTime(iso) {
  return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>
