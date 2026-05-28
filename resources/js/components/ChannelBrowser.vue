<template>
  <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-2xl w-full max-w-lg mx-4 flex flex-col max-h-[80vh] overflow-hidden">
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
        <h2 class="font-bold text-white">Browse Channels</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="px-4 py-2 border-b border-gray-700">
        <input
          v-model="q"
          type="text"
          placeholder="Search channels..."
          class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      <div class="overflow-y-auto flex-1">
        <div
          v-for="room in filtered"
          :key="room.id"
          class="flex items-center justify-between px-4 py-3 hover:bg-gray-700 cursor-pointer border-b border-gray-700/50"
          @click="select(room)"
        >
          <div>
            <div class="flex items-center gap-2">
              <span class="text-gray-400 text-sm">#</span>
              <span class="font-medium text-white text-sm">{{ room.name }}</span>
              <span v-if="room.type !== 'public'" class="text-xs bg-gray-700 text-gray-400 px-1.5 py-0.5 rounded">{{ room.type }}</span>
            </div>
            <div v-if="room.description" class="text-xs text-gray-500 mt-0.5 ml-4">{{ room.description }}</div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">{{ room.member_count }} members</span>
            <button
              v-if="!room.joined"
              @click.stop="join(room)"
              class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-2 py-1 rounded"
            >Join</button>
          </div>
        </div>
        <div v-if="filtered.length === 0" class="p-6 text-center text-gray-500 text-sm">No channels found</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useChatStore } from '@/stores/chat'

const emit = defineEmits(['close', 'select'])
const chat = useChatStore()
const q = ref('')

const filtered = computed(() => {
    const term = q.value.toLowerCase()
    return chat.rooms.filter(r =>
        r.name.toLowerCase().includes(term) ||
        (r.description || '').toLowerCase().includes(term)
    )
})

async function join(room) {
    await chat.joinRoom(room.id)
    await chat.fetchRooms()
}

function select(room) {
    emit('select', room.id)
    emit('close')
}
</script>
