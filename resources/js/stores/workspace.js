import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useWorkspaceStore = defineStore('workspace', () => {
    const workspaces = ref([])
    const currentWorkspace = ref(null)
    const loading = ref(false)

    const sortedWorkspaces = computed(() =>
        [...workspaces.value].sort((a, b) => a.name.localeCompare(b.name))
    )

    async function fetchWorkspaces() {
        loading.value = true
        try {
            const { data } = await axios.get('/api/v1/workspaces')
            workspaces.value = data.data
        } finally {
            loading.value = false
        }
    }

    async function createWorkspace(payload) {
        const { data } = await axios.post('/api/v1/workspaces', payload)
        workspaces.value.push(data.data)
        return data.data
    }

    async function joinWorkspace(workspace) {
        await axios.post(`/api/v1/workspaces/${workspace.id}/join`)
        await fetchWorkspaces()
    }

    function setCurrentWorkspace(ws) {
        currentWorkspace.value = ws
    }

    async function loadFromRoute(slug) {
        if (!slug) return
        // Already loaded with matching slug
        if (currentWorkspace.value?.slug === slug) return
        // Find in cached list first
        if (workspaces.value.length === 0) await fetchWorkspaces()
        const found = workspaces.value.find(w => w.slug === slug)
        if (found) { currentWorkspace.value = found; return }
        // Fallback: fetch by slug from API
        try {
            const { data } = await axios.get(`/api/v1/workspaces/${slug}`)
            currentWorkspace.value = data.data
        } catch { /* workspace not found or no access */ }
    }

    async function fetchStorageStats(workspaceId) {
        const { data } = await axios.get(`/api/v1/workspaces/${workspaceId}/storage`)
        return data
    }

    async function updateMemberRole(workspaceId, userId, role) {
        await axios.put(`/api/v1/workspaces/${workspaceId}/members/${userId}/role`, { role })
    }

    async function removeMember(workspaceId, userId) {
        await axios.delete(`/api/v1/workspaces/${workspaceId}/members/${userId}`)
    }

    return {
        workspaces,
        currentWorkspace,
        loading,
        sortedWorkspaces,
        fetchWorkspaces,
        createWorkspace,
        joinWorkspace,
        setCurrentWorkspace,
        loadFromRoute,
        fetchStorageStats,
        updateMemberRole,
        removeMember,
    }
})
