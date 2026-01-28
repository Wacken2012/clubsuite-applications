<template>
  <div class="application-list">
    <div class="list-header">
      <h3>Antragsliste</h3>
      <div class="list-filters">
        <NcSelect
          v-model="filterStatus"
          :options="statusFilterOptions"
          label="label"
          track-by="value"
          placeholder="Status filtern..."
          :reduce="option => option.value"
          @input="loadApplications"
          style="width: 180px;"
        />
        <NcTextField
          v-model="searchQuery"
          label="Suche"
          placeholder="Suchen..."
          @input="debouncedSearch"
          style="width: 200px;"
        />
      </div>
    </div>

    <NcLoadingIcon v-if="loading" :size="44" class="loading-spinner" />

    <div v-else-if="items.length === 0" class="empty-state">
      <NcEmptyContent
        name="Keine Anträge"
        description="Es wurden noch keine Anträge erstellt."
      >
        <template #icon>
          <FileDocumentOutline :size="64" />
        </template>
      </NcEmptyContent>
    </div>

    <table v-else class="applications-table">
      <thead>
        <tr>
          <th @click="toggleSort('id')" class="sortable">
            ID
            <SortIcon :active="sortBy === 'id'" :direction="sortOrder" />
          </th>
          <th @click="toggleSort('title')" class="sortable">
            Titel
            <SortIcon :active="sortBy === 'title'" :direction="sortOrder" />
          </th>
          <th @click="toggleSort('type')" class="sortable">
            Typ
            <SortIcon :active="sortBy === 'type'" :direction="sortOrder" />
          </th>
          <th @click="toggleSort('status')" class="sortable">
            Status
            <SortIcon :active="sortBy === 'status'" :direction="sortOrder" />
          </th>
          <th @click="toggleSort('created_at')" class="sortable">
            Erstellt
            <SortIcon :active="sortBy === 'created_at'" :direction="sortOrder" />
          </th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="app in items" :key="app.id" :class="{ 'row-approved': app.status === 'approved', 'row-rejected': app.status === 'rejected' }">
          <td>{{ app.id }}</td>
          <td>{{ app.title }}</td>
          <td>
            <span class="type-badge" :class="'type-' + app.type">
              {{ getTypeLabel(app.type) }}
            </span>
          </td>
          <td>
            <span class="status-badge" :class="'status-' + app.status">
              {{ getStatusLabel(app.status) }}
            </span>
          </td>
          <td>{{ formatDate(app.created_at) }}</td>
          <td class="actions-cell">
            <NcButton type="tertiary" @click="$emit('edit', app)" title="Bearbeiten">
              <template #icon><Pencil :size="18" /></template>
            </NcButton>
            <NcButton 
              v-if="app.status === 'pending'" 
              type="tertiary" 
              @click="approveApplication(app.id)"
              title="Genehmigen"
            >
              <template #icon><Check :size="18" /></template>
            </NcButton>
            <NcButton 
              v-if="app.status === 'pending'" 
              type="tertiary" 
              @click="rejectApplication(app.id)"
              title="Ablehnen"
            >
              <template #icon><Close :size="18" /></template>
            </NcButton>
            <NcButton type="tertiary" @click="confirmDelete(app)" title="Löschen">
              <template #icon><Delete :size="18" /></template>
            </NcButton>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="pagination" v-if="total > limit">
      <NcButton type="tertiary" @click="prevPage" :disabled="offset === 0">
        <template #icon><ChevronLeft :size="20" /></template>
        Zurück
      </NcButton>
      <span class="pagination-info">
        {{ offset + 1 }} - {{ Math.min(offset + limit, total) }} von {{ total }}
      </span>
      <NcButton type="tertiary" @click="nextPage" :disabled="offset + limit >= total">
        Weiter
        <template #icon><ChevronRight :size="20" /></template>
      </NcButton>
    </div>

    <!-- Delete Confirmation Modal -->
    <NcModal v-if="showDeleteModal" @close="showDeleteModal = false">
      <div class="modal-content">
        <h3>Antrag löschen?</h3>
        <p>Möchten Sie den Antrag "{{ applicationToDelete?.title }}" wirklich löschen?</p>
        <p class="warning-text">Diese Aktion kann nicht rückgängig gemacht werden.</p>
        <div class="modal-actions">
          <NcButton type="tertiary" @click="showDeleteModal = false">Abbrechen</NcButton>
          <NcButton type="error" @click="deleteApplication">
            <template #icon><Delete :size="20" /></template>
            Löschen
          </NcButton>
        </div>
      </div>
    </NcModal>
  </div>
</template>

<script>
import { NcButton, NcTextField, NcSelect, NcLoadingIcon, NcEmptyContent, NcModal } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Check from 'vue-material-design-icons/Check.vue'
import Close from 'vue-material-design-icons/Close.vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'

// Simple sort icon component
const SortIcon = {
  props: ['active', 'direction'],
  template: `<span class="sort-icon" :class="{ active: active }">{{ active ? (direction === 'ASC' ? '▲' : '▼') : '⇅' }}</span>`
}

export default {
  name: 'ApplicationList',
  components: {
    NcButton,
    NcTextField,
    NcSelect,
    NcLoadingIcon,
    NcEmptyContent,
    NcModal,
    Pencil,
    Delete,
    Check,
    Close,
    ChevronLeft,
    ChevronRight,
    FileDocumentOutline,
    SortIcon
  },
  data() {
    return {
      items: [],
      total: 0,
      limit: 20,
      offset: 0,
      sortBy: 'created_at',
      sortOrder: 'DESC',
      loading: false,
      searchQuery: '',
      filterStatus: null,
      showDeleteModal: false,
      applicationToDelete: null,
      searchTimeout: null,
      statusFilterOptions: [
        { value: null, label: 'Alle Status' },
        { value: 'pending', label: 'Ausstehend' },
        { value: 'approved', label: 'Genehmigt' },
        { value: 'rejected', label: 'Abgelehnt' }
      ]
    }
  },
  mounted() {
    this.loadApplications()
  },
  methods: {
    async loadApplications() {
      this.loading = true
      try {
        const params = new URLSearchParams({
          limit: String(this.limit),
          offset: String(this.offset),
          sort: this.sortBy,
          order: this.sortOrder
        })
        
        if (this.filterStatus) {
          params.append('status', this.filterStatus)
        }
        if (this.searchQuery) {
          params.append('search', this.searchQuery)
        }

        const url = generateUrl('/apps/clubsuite-applications/api/applications_paginated?' + params.toString())
        const response = await axios.get(url)
        
        this.items = response.data.rows || response.data.data || []
        this.total = response.data.total || 0
      } catch (error) {
        console.error('Error loading applications:', error)
        showError('Fehler beim Laden der Anträge.')
      } finally {
        this.loading = false
      }
    },
    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.offset = 0
        this.loadApplications()
      }, 300)
    },
    toggleSort(column) {
      if (this.sortBy === column) {
        this.sortOrder = this.sortOrder === 'ASC' ? 'DESC' : 'ASC'
      } else {
        this.sortBy = column
        this.sortOrder = 'DESC'
      }
      this.loadApplications()
    },
    nextPage() {
      if (this.offset + this.limit < this.total) {
        this.offset += this.limit
        this.loadApplications()
      }
    },
    prevPage() {
      if (this.offset >= this.limit) {
        this.offset -= this.limit
        this.loadApplications()
      }
    },
    async approveApplication(id) {
      try {
        const url = generateUrl(`/apps/clubsuite-applications/api/applications/${id}/approve`)
        await axios.post(url)
        showSuccess('Antrag wurde genehmigt.')
        this.loadApplications()
        this.$emit('refresh')
      } catch (error) {
        console.error('Error approving application:', error)
        showError('Fehler beim Genehmigen des Antrags.')
      }
    },
    async rejectApplication(id) {
      try {
        const url = generateUrl(`/apps/clubsuite-applications/api/applications/${id}/reject`)
        await axios.post(url)
        showSuccess('Antrag wurde abgelehnt.')
        this.loadApplications()
        this.$emit('refresh')
      } catch (error) {
        console.error('Error rejecting application:', error)
        showError('Fehler beim Ablehnen des Antrags.')
      }
    },
    confirmDelete(application) {
      this.applicationToDelete = application
      this.showDeleteModal = true
    },
    async deleteApplication() {
      if (!this.applicationToDelete) return
      
      try {
        const url = generateUrl(`/apps/clubsuite-applications/api/applications/${this.applicationToDelete.id}`)
        await axios.delete(url)
        showSuccess('Antrag wurde gelöscht.')
        this.showDeleteModal = false
        this.applicationToDelete = null
        this.loadApplications()
        this.$emit('refresh')
      } catch (error) {
        console.error('Error deleting application:', error)
        showError('Fehler beim Löschen des Antrags.')
      }
    },
    getTypeLabel(type) {
      const types = {
        'membership': 'Mitgliedsantrag',
        'fee_reduction': 'Beitragsermäßigung',
        'resignation': 'Kündigung',
        'other': 'Sonstiges'
      }
      return types[type] || type
    },
    getStatusLabel(status) {
      const statuses = {
        'pending': 'Ausstehend',
        'approved': 'Genehmigt',
        'rejected': 'Abgelehnt'
      }
      return statuses[status] || status
    },
    formatDate(dateStr) {
      if (!dateStr) return '-'
      try {
        const date = new Date(dateStr)
        return date.toLocaleDateString('de-DE', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        })
      } catch {
        return dateStr
      }
    }
  }
}
</script>

<style scoped>
.application-list {
  padding: 10px;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 10px;
}

.list-header h3 {
  margin: 0;
  font-size: 18px;
}

.list-filters {
  display: flex;
  gap: 12px;
  align-items: center;
}

.loading-spinner {
  display: block;
  margin: 40px auto;
}

.empty-state {
  padding: 40px;
  text-align: center;
}

.applications-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--color-main-background);
  border-radius: var(--border-radius);
  overflow: hidden;
}

.applications-table th,
.applications-table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

.applications-table th {
  background: var(--color-background-dark);
  font-weight: 600;
  white-space: nowrap;
}

.applications-table th.sortable {
  cursor: pointer;
  user-select: none;
}

.applications-table th.sortable:hover {
  background: var(--color-background-hover);
}

.sort-icon {
  margin-left: 4px;
  opacity: 0.4;
  font-size: 10px;
}

.sort-icon.active {
  opacity: 1;
  color: var(--color-primary);
}

.applications-table tr:hover {
  background: var(--color-background-hover);
}

.row-approved {
  background: rgba(70, 186, 97, 0.1) !important;
}

.row-rejected {
  background: rgba(224, 36, 36, 0.1) !important;
}

.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-pending {
  background: var(--color-warning);
  color: #000;
}

.status-approved {
  background: var(--color-success);
  color: #fff;
}

.status-rejected {
  background: var(--color-error);
  color: #fff;
}

.type-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  background: var(--color-background-dark);
}

.type-membership {
  background: #e3f2fd;
  color: #1565c0;
}

.type-fee_reduction {
  background: #fff3e0;
  color: #ef6c00;
}

.type-resignation {
  background: #fce4ec;
  color: #c62828;
}

.actions-cell {
  white-space: nowrap;
}

.actions-cell button {
  margin: 0 2px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.pagination-info {
  color: var(--color-text-maxcontrast);
}

.modal-content {
  padding: 20px;
}

.modal-content h3 {
  margin-top: 0;
}

.warning-text {
  color: var(--color-error);
  font-size: 13px;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
