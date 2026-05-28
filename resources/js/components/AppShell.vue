<template>
  <div class="h-screen bg-gray-950 text-gray-100 flex flex-col">
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useNotificationStore } from '../stores/notifications'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const notifications = useNotificationStore()
const router = useRouter()

onMounted(async () => {
  try {
    await auth.fetchUser()
    if (auth.user) {
      notifications.subscribeForUser(auth.user.id)
      notifications.fetchCount()
    }
  } catch {
    // Not authenticated — router guard will redirect
  }
})
</script>
