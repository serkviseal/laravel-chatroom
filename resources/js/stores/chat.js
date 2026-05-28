import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import echo from '@/echo'

export const useChatStore = defineStore('chat', () => {
    const rooms = ref([])
    const activeRoomId = ref(null)
    const messagesByRoom = ref({})
    const onlineUsers = ref({})
    const typingUsers = ref({})
    const typingTimers = {}

    const activeRoom = computed(() => rooms.value.find(r => r.id === activeRoomId.value))
    const activeMessages = computed(() => messagesByRoom.value[activeRoomId.value] ?? [])

    async function fetchRooms() {
        const { data } = await axios.get('/api/v1/rooms')
        rooms.value = data.data
    }

    async function joinRoom(roomId) {
        await axios.post(`/api/v1/rooms/${roomId}/join`)
        await fetchRooms()
        await selectRoom(roomId)
    }

    async function selectRoom(roomId) {
        if (activeRoomId.value === roomId) return

        if (activeRoomId.value) {
            echo.leave(`room.${activeRoomId.value}`)
        }

        activeRoomId.value = roomId

        if (!messagesByRoom.value[roomId]) {
            messagesByRoom.value[roomId] = []
        }

        await fetchMessages(roomId)
        subscribeToRoom(roomId)
    }

    async function fetchMessages(roomId, page = 1) {
        const { data } = await axios.get(`/api/v1/rooms/${roomId}/messages`, { params: { page } })
        // API returns newest-first (latest()), so reverse for display
        const msgs = [...data.data].reverse()
        if (page === 1) {
            messagesByRoom.value[roomId] = msgs
        } else {
            messagesByRoom.value[roomId] = [...msgs, ...messagesByRoom.value[roomId]]
        }
    }

    async function sendMessage(body, roomId) {
        const { data } = await axios.post('/api/v1/messages', { body, room_id: roomId })
        messagesByRoom.value[roomId].push(data.data)
    }

    async function editMessage(messageId, body) {
        const { data } = await axios.put(`/api/v1/messages/${messageId}`, { body })
        replaceMessage(data.data)
    }

    async function deleteMessage(messageId) {
        const { data } = await axios.delete(`/api/v1/messages/${messageId}`)
        replaceMessage(data.data)
    }

    async function reactToMessage(messageId, emoji) {
        const { data } = await axios.post(`/api/v1/messages/${messageId}/react`, { emoji })
        replaceMessage(data.data)
    }

    function replaceMessage(updated) {
        const list = messagesByRoom.value[updated.room_id]
        if (!list) return
        const idx = list.findIndex(m => m.id === updated.id)
        if (idx !== -1) list.splice(idx, 1, updated)
    }

    function subscribeToRoom(roomId) {
        echo.join(`room.${roomId}`)
            .here(users => { onlineUsers.value[roomId] = users })
            .joining(user => {
                if (!onlineUsers.value[roomId]) onlineUsers.value[roomId] = []
                onlineUsers.value[roomId].push(user)
            })
            .leaving(user => {
                if (onlineUsers.value[roomId]) {
                    onlineUsers.value[roomId] = onlineUsers.value[roomId].filter(u => u.id !== user.id)
                }
            })
            .listen('MessageCreated', msg => {
                if (!messagesByRoom.value[roomId]) messagesByRoom.value[roomId] = []
                messagesByRoom.value[roomId].push(msg)
            })
            .listen('MessageUpdated', msg => replaceMessage(msg))
            .listen('MessageReacted', msg => replaceMessage(msg))
            .listenForWhisper('typing', ({ user }) => {
                if (!typingUsers.value[roomId]) typingUsers.value[roomId] = {}
                typingUsers.value[roomId][user.id] = user  // store full user object

                clearTimeout(typingTimers[user.id])
                typingTimers[user.id] = setTimeout(() => {
                    if (typingUsers.value[roomId]) {
                        delete typingUsers.value[roomId][user.id]
                    }
                }, 3000)
            })
    }

    function whisperTyping(roomId, user) {
        echo.join(`room.${roomId}`).whisper('typing', { user })
    }

    return {
        rooms, activeRoomId, activeRoom, activeMessages,
        onlineUsers, typingUsers,
        fetchRooms, joinRoom, selectRoom, fetchMessages,
        sendMessage, editMessage, deleteMessage, reactToMessage,
        whisperTyping,
    }
})
