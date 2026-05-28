import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useFileStore = defineStore('files', () => {
    const files = ref([])
    const uploading = ref(false)
    const uploadProgress = ref(0)
    const nextPageUrl = ref(null)

    async function fetchFiles(workspaceId, { type = null, channelId = null } = {}) {
        const params = {}
        if (type) params.type = type
        if (channelId) params.channel_id = channelId
        const { data } = await axios.get(`/api/v1/workspaces/${workspaceId}/files`, { params })
        files.value = data.data
        nextPageUrl.value = data.links?.next
    }

    async function uploadFile(workspaceId, file, channelId = null) {
        uploading.value = true
        uploadProgress.value = 0
        const formData = new FormData()
        formData.append('file', file)
        if (channelId) formData.append('channel_id', channelId)
        try {
            const { data } = await axios.post(
                `/api/v1/workspaces/${workspaceId}/files`,
                formData,
                {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    onUploadProgress: e => {
                        uploadProgress.value = Math.round((e.loaded * 100) / e.total)
                    },
                }
            )
            files.value.unshift(data.data)
            return data.data
        } finally {
            uploading.value = false
        }
    }

    async function shareFile(fileId) {
        const { data } = await axios.post(`/api/v1/files/${fileId}/share`)
        return data
    }

    async function deleteFile(fileId) {
        await axios.delete(`/api/v1/files/${fileId}`)
        files.value = files.value.filter(f => f.id !== fileId)
    }

    return {
        files,
        uploading,
        uploadProgress,
        nextPageUrl,
        fetchFiles,
        uploadFile,
        shareFile,
        deleteFile,
    }
})
