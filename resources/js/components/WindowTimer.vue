<template>
  <span v-if="remaining > 0" :class="colorClass" class="text-xs font-mono flex items-center gap-1">
    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ display }}
  </span>
  <span v-else class="text-xs text-red-400 font-medium">Window expired</span>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({ expiresAt: String })

const now = ref(Date.now())
let timer = null

onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 10000) })
onUnmounted(() => clearInterval(timer))

const remaining = computed(() => {
  const exp = new Date(props.expiresAt).getTime()
  return Math.max(0, exp - now.value)
})

const display = computed(() => {
  const s = Math.floor(remaining.value / 1000)
  const h = Math.floor(s / 3600)
  const m = Math.floor((s % 3600) / 60)
  if (h > 0) return `${h}h ${m}m`
  return `${m}m`
})

const colorClass = computed(() => {
  const h = remaining.value / 3600000
  if (h > 8) return 'text-green-400'
  if (h > 2) return 'text-yellow-400'
  return 'text-red-400'
})
</script>
