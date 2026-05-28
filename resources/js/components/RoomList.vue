<template>
  <div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto px-2 py-1 space-y-0.5">

      <!-- Joined channels -->
      <div v-if="joined.length" class="px-2 pt-2 pb-0.5">
        <span class="text-xs font-semibold text-gray-600 uppercase tracking-widest">Channels</span>
      </div>
      <button
        v-for="room in joined"
        :key="room.id"
        @click="$emit('select', room.id)"
        class="sidebar-item w-full"
        :class="activeRoomId === room.id ? 'sidebar-item-active' : ''"
      >
        <span class="text-gray-500 font-mono text-xs leading-none">#</span>
        <span class="flex-1 truncate">{{ room.name }}</span>
        <span v-if="room.type === 'private'" class="text-gray-700 text-xs" title="Private">🔒</span>
      </button>

      <!-- Unjoined channels (top 5) -->
      <template v-if="unjoined.length">
        <div class="px-2 pt-3 pb-0.5">
          <span class="text-xs font-semibold text-gray-700 uppercase tracking-widest">Available</span>
        </div>
        <button
          v-for="room in unjoined"
          :key="room.id"
          @click="$emit('join', room.id)"
          class="sidebar-item w-full text-gray-600 hover:text-gray-300"
        >
          <span class="text-gray-700 font-mono text-xs leading-none">#</span>
          <span class="flex-1 truncate">{{ room.name }}</span>
          <span class="text-xs text-brand-400 font-medium">Join</span>
        </button>
      </template>

      <div v-if="!rooms.length" class="px-3 py-6 text-center">
        <p class="text-xs text-gray-600">No channels yet</p>
      </div>
    </div>

    <!-- Footer actions -->
    <div class="px-2 py-2 border-t border-gray-800 flex-shrink-0 space-y-0.5">
      <button
        @click="$emit('create')"
        class="sidebar-item w-full text-gray-500 hover:text-brand-400"
      >
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Add channel</span>
      </button>
      <button
        @click="$emit('browse')"
        class="sidebar-item w-full text-gray-500 hover:text-gray-300"
      >
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
        </svg>
        <span>Browse all</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  rooms:        { type: Array,  default: () => [] },
  activeRoomId: { type: Number, default: null },
})

defineEmits(['select', 'join', 'create', 'browse'])

const joined   = computed(() => props.rooms.filter(r => r.joined))
const unjoined = computed(() => props.rooms.filter(r => !r.joined).slice(0, 5))
</script>
