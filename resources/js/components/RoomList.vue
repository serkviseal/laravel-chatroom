<template>
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- Search -->
    <div class="px-3 py-2">
      <input
        v-model="search"
        type="text"
        placeholder="Search rooms..."
        class="w-full bg-gray-800 text-sm text-gray-100 placeholder-gray-500 rounded-lg px-3 py-1.5 border border-gray-700 focus:outline-none focus:border-brand-500"
      />
    </div>

    <!-- Room list -->
    <ul class="flex-1 overflow-y-auto px-2 space-y-0.5">
      <li v-for="room in filtered" :key="room.id">
        <button
          @click="select(room)"
          class="sidebar-item w-full text-left"
          :class="activeRoomId === room.id ? 'sidebar-item-active' : 'text-gray-400'"
        >
          <span class="text-gray-500 text-xs">#</span>
          <span class="flex-1 truncate text-sm">{{ room.name }}</span>
          <span v-if="!room.joined" class="text-xs text-brand-400 hover:underline" @click.stop="$emit('join', room.id)">
            Join
          </span>
        </button>
      </li>
      <li v-if="filtered.length === 0" class="px-3 py-4 text-xs text-gray-600 text-center">
        No rooms found
      </li>
    </ul>

    <!-- Create room button -->
    <div class="px-3 py-2 border-t border-gray-800">
      <button
        @click="$emit('create')"
        class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-gray-500 hover:text-brand-400 hover:bg-gray-800 rounded-lg transition-colors"
      >
        <span class="text-base leading-none">+</span>
        New Room
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  rooms: { type: Array, default: () => [] },
  activeRoomId: { type: Number, default: null },
})

const emit = defineEmits(['select', 'join', 'create'])

const search = ref('')

const filtered = computed(() =>
  props.rooms.filter(r =>
    r.name.toLowerCase().includes(search.value.toLowerCase())
  )
)

function select(room) {
  if (room.joined) {
    emit('select', room.id)
  }
}
</script>
