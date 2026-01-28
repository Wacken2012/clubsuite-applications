import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const apiUrl = generateUrl('/apps/clubsuite-applications/api')

export default {
    /**
     * Fetch paginated applications list
     */
    async getApplications(limit = 25, offset = 0, sort = 'created_at', order = 'DESC') {
        const response = await axios.get(`${apiUrl}/applications`, {
            params: { limit, offset, sort, order }
        })
        return response.data
    },

    /**
     * Fetch single application by ID
     */
    async getApplication(id) {
        const response = await axios.get(`${apiUrl}/applications/${id}`)
        return response.data
    },

    /**
     * Create new application
     */
    async createApplication(data) {
        const response = await axios.post(`${apiUrl}/applications`, data)
        return response.data
    },

    /**
     * Update existing application
     */
    async updateApplication(id, data) {
        const response = await axios.put(`${apiUrl}/applications/${id}`, data)
        return response.data
    },

    /**
     * Delete application
     */
    async deleteApplication(id) {
        const response = await axios.delete(`${apiUrl}/applications/${id}`)
        return response.data
    },

    /**
     * Approve application
     */
    async approveApplication(id) {
        const response = await axios.post(`${apiUrl}/applications/${id}/approve`)
        return response.data
    },

    /**
     * Reject application
     */
    async rejectApplication(id) {
        const response = await axios.post(`${apiUrl}/applications/${id}/reject`)
        return response.data
    }
}
