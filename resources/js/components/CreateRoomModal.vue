<template>
  <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
      <h2 class="text-lg font-semibold mb-4">Create a Room</h2>
      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block text-sm text-gray-400 mb-1">Room name</label>
          <input
            v-model="name"
            type="text"
            required
            maxlength="100"
            placeholder="e.g. backend-dev"
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500"
          />
          <p v-if="errors.name" class="text-red-400 text-xs mt-1">{{ errors.name[0] }}</p>
        </div>
        <div class="mb-6">
          <label class="block text-sm text-gray-400 mb-1">Description (optional)</label>
          <input
            v-model="description"
            type="text"
            maxlength="255"
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500"
          />
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm text-gray-400 hover:text-white">
            Cancel
          </button>
          <button type="submit" :disabled="loading" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg disabled:opacity-40">
            Create
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const emit = defineEmits(['close', 'created'])

const name = ref('')
const description = ref('')
const loading = ref(false)
const errors = ref({})

async function submit() {
  loading.value = true
  errors.value = {}
  try {
    const { data } = await axios.post('/api/v1/rooms', {
      name: name.value,
      description: description.value,
    })
    emit('created', data.data)
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors
    }
  } finally {
    loading.value = false
  }
}
</script>
