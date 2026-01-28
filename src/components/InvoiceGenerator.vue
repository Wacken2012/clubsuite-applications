<template>
  <div class="invoice-generator">
    <h3>Rechnungsgenerator</h3>
    <p class="description">
      Erstellen Sie Abschlussrechnungen für genehmigte Mitgliedsanträge und andere kostenpflichtige Vorgänge.
    </p>

    <div class="generator-sections">
      <!-- Filter Section -->
      <section class="filter-section">
        <h4>Filter</h4>
        <div class="filter-row">
          <NcSelect
            v-model="filterType"
            :options="typeOptions"
            label="label"
            track-by="value"
            placeholder="Antragstyp..."
            :reduce="option => option.value"
            style="width: 200px;"
          />
          <NcTextField
            v-model="filterDateFrom"
            label="Von Datum"
            type="date"
          />
          <NcTextField
            v-model="filterDateTo"
            label="Bis Datum"
            type="date"
          />
          <NcButton @click="loadApprovedApplications">
            <template #icon><Refresh :size="20" /></template>
            Aktualisieren
          </NcButton>
        </div>
      </section>

      <!-- Approved Applications List -->
      <section class="applications-section">
        <h4>Genehmigte Anträge ({{ approvedApplications.length }})</h4>
        
        <NcLoadingIcon v-if="loading" :size="32" />
        
        <div v-else-if="approvedApplications.length === 0" class="empty-notice">
          <FileDocumentOutline :size="32" />
          <p>Keine genehmigten Anträge gefunden.</p>
        </div>

        <div v-else class="applications-select">
          <div class="select-all">
            <NcCheckboxRadioSwitch 
              v-model="selectAll" 
              type="checkbox"
              @update:modelValue="toggleSelectAll"
            >
              Alle auswählen
            </NcCheckboxRadioSwitch>
          </div>
          
          <div 
            v-for="app in approvedApplications" 
            :key="app.id" 
            class="application-item"
            :class="{ selected: selectedIds.includes(app.id) }"
          >
            <NcCheckboxRadioSwitch 
              :checked="selectedIds.includes(app.id)"
              type="checkbox"
              @update:checked="toggleSelection(app.id)"
            >
              <div class="app-info">
                <strong>{{ app.title }}</strong>
                <span class="app-meta">
                  {{ getTypeLabel(app.type) }} · 
                  Genehmigt: {{ formatDate(app.approved_at) }}
                </span>
              </div>
            </NcCheckboxRadioSwitch>
          </div>
        </div>
      </section>

      <!-- Invoice Settings -->
      <section class="settings-section" v-if="selectedIds.length > 0">
        <h4>Rechnungseinstellungen</h4>
        
        <div class="settings-grid">
          <div class="setting-group">
            <label>Rechnungsnummer-Präfix</label>
            <NcTextField v-model="invoiceSettings.prefix" placeholder="RE-" />
          </div>
          
          <div class="setting-group">
            <label>Startingnummer</label>
            <NcTextField v-model="invoiceSettings.startNumber" type="number" />
          </div>
          
          <div class="setting-group">
            <label>Rechnungsdatum</label>
            <NcTextField v-model="invoiceSettings.date" type="date" />
          </div>
          
          <div class="setting-group">
            <label>Fälligkeitsdatum</label>
            <NcTextField v-model="invoiceSettings.dueDate" type="date" />
          </div>

          <div class="setting-group full-width">
            <label>Standardbetrag (€)</label>
            <NcTextField v-model="invoiceSettings.defaultAmount" type="number" step="0.01" placeholder="0.00" />
          </div>

          <div class="setting-group full-width">
            <label>Rechnungstext</label>
            <textarea 
              v-model="invoiceSettings.text" 
              class="nc-textarea"
              rows="3"
              placeholder="Vielen Dank für Ihre Mitgliedschaft..."
            ></textarea>
          </div>
        </div>
      </section>

      <!-- Preview & Generate -->
      <section class="generate-section" v-if="selectedIds.length > 0">
        <h4>Rechnungen erstellen</h4>
        
        <div class="summary-box">
          <div class="summary-item">
            <span class="summary-label">Ausgewählte Anträge:</span>
            <span class="summary-value">{{ selectedIds.length }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Gesamtbetrag:</span>
            <span class="summary-value">{{ formatCurrency(calculateTotal()) }}</span>
          </div>
        </div>

        <div class="generate-actions">
          <NcButton type="secondary" @click="previewInvoices">
            <template #icon><Eye :size="20" /></template>
            Vorschau
          </NcButton>
          <NcButton type="primary" @click="generateInvoices" :disabled="generating">
            <template #icon>
              <NcLoadingIcon v-if="generating" :size="20" />
              <FileExport v-else :size="20" />
            </template>
            {{ generating ? 'Wird erstellt...' : 'Rechnungen erstellen' }}
          </NcButton>
        </div>
      </section>

      <!-- Generated Invoices History -->
      <section class="history-section" v-if="generatedInvoices.length > 0">
        <h4>Erstellte Rechnungen</h4>
        <table class="invoices-table">
          <thead>
            <tr>
              <th>Rechnungsnr.</th>
              <th>Antrag</th>
              <th>Betrag</th>
              <th>Datum</th>
              <th>Aktionen</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inv in generatedInvoices" :key="inv.number">
              <td>{{ inv.number }}</td>
              <td>{{ inv.applicationTitle }}</td>
              <td>{{ formatCurrency(inv.amount) }}</td>
              <td>{{ inv.date }}</td>
              <td>
                <NcButton type="tertiary" @click="downloadInvoice(inv)">
                  <template #icon><Download :size="18" /></template>
                </NcButton>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </div>
</template>

<script>
import { NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch, NcLoadingIcon } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError, showWarning } from '@nextcloud/dialogs'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
import FileExport from 'vue-material-design-icons/FileExport.vue'
import Download from 'vue-material-design-icons/Download.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'

export default {
  name: 'InvoiceGenerator',
  components: {
    NcButton,
    NcTextField,
    NcSelect,
    NcCheckboxRadioSwitch,
    NcLoadingIcon,
    Refresh,
    Eye,
    FileExport,
    Download,
    FileDocumentOutline
  },
  data() {
    const today = new Date().toISOString().split('T')[0]
    const dueDate = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    
    return {
      loading: false,
      generating: false,
      approvedApplications: [],
      selectedIds: [],
      selectAll: false,
      filterType: null,
      filterDateFrom: '',
      filterDateTo: '',
      invoiceSettings: {
        prefix: 'RE-',
        startNumber: 1001,
        date: today,
        dueDate: dueDate,
        defaultAmount: 120.00,
        text: 'Vielen Dank für Ihre Mitgliedschaft in unserem Verein.\n\nBitte überweisen Sie den Betrag bis zum angegebenen Fälligkeitsdatum.'
      },
      generatedInvoices: [],
      typeOptions: [
        { value: null, label: 'Alle Typen' },
        { value: 'membership', label: 'Mitgliedsantrag' },
        { value: 'fee_reduction', label: 'Beitragsermäßigung' },
        { value: 'other', label: 'Sonstiges' }
      ]
    }
  },
  mounted() {
    this.loadApprovedApplications()
  },
  methods: {
    async loadApprovedApplications() {
      this.loading = true
      try {
        const params = new URLSearchParams({
          limit: '100',
          offset: '0',
          status: 'approved'
        })
        
        if (this.filterType) {
          params.append('type', this.filterType)
        }

        const url = generateUrl('/apps/clubsuite-applications/api/applications_paginated?' + params.toString())
        const response = await axios.get(url)
        
        let apps = response.data.rows || response.data.data || []
        
        // Client-side date filtering
        if (this.filterDateFrom) {
          const from = new Date(this.filterDateFrom)
          apps = apps.filter(a => new Date(a.approved_at) >= from)
        }
        if (this.filterDateTo) {
          const to = new Date(this.filterDateTo)
          apps = apps.filter(a => new Date(a.approved_at) <= to)
        }
        
        this.approvedApplications = apps
        this.selectedIds = []
        this.selectAll = false
      } catch (error) {
        console.error('Error loading approved applications:', error)
        showError('Fehler beim Laden der genehmigten Anträge.')
      } finally {
        this.loading = false
      }
    },
    toggleSelectAll(value) {
      if (value) {
        this.selectedIds = this.approvedApplications.map(a => a.id)
      } else {
        this.selectedIds = []
      }
    },
    toggleSelection(id) {
      const index = this.selectedIds.indexOf(id)
      if (index === -1) {
        this.selectedIds.push(id)
      } else {
        this.selectedIds.splice(index, 1)
      }
      this.selectAll = this.selectedIds.length === this.approvedApplications.length
    },
    calculateTotal() {
      return this.selectedIds.length * parseFloat(this.invoiceSettings.defaultAmount || 0)
    },
    previewInvoices() {
      showWarning('Vorschau-Funktion wird in einer zukünftigen Version implementiert.')
    },
    async generateInvoices() {
      if (this.selectedIds.length === 0) {
        showError('Bitte wählen Sie mindestens einen Antrag aus.')
        return
      }

      this.generating = true
      try {
        // Generate invoices locally (in a real app, this would call a backend endpoint)
        const newInvoices = []
        let currentNumber = parseInt(this.invoiceSettings.startNumber)

        for (const id of this.selectedIds) {
          const app = this.approvedApplications.find(a => a.id === id)
          if (app) {
            newInvoices.push({
              number: `${this.invoiceSettings.prefix}${currentNumber}`,
              applicationId: app.id,
              applicationTitle: app.title,
              amount: parseFloat(this.invoiceSettings.defaultAmount),
              date: this.invoiceSettings.date,
              dueDate: this.invoiceSettings.dueDate,
              text: this.invoiceSettings.text
            })
            currentNumber++
          }
        }

        // Add to history
        this.generatedInvoices = [...newInvoices, ...this.generatedInvoices]
        
        // Update start number for next batch
        this.invoiceSettings.startNumber = currentNumber

        // Clear selection
        this.selectedIds = []
        this.selectAll = false

        showSuccess(`${newInvoices.length} Rechnung(en) wurden erstellt.`)
      } catch (error) {
        console.error('Error generating invoices:', error)
        showError('Fehler beim Erstellen der Rechnungen.')
      } finally {
        this.generating = false
      }
    },
    downloadInvoice(invoice) {
      // Generate a simple text-based invoice for download
      const content = `
RECHNUNG
========

Rechnungsnummer: ${invoice.number}
Rechnungsdatum: ${invoice.date}
Fälligkeitsdatum: ${invoice.dueDate}

Bezug: ${invoice.applicationTitle}

Betrag: ${this.formatCurrency(invoice.amount)}

${invoice.text}

----------------------------------------
Diese Rechnung wurde automatisch erstellt.
      `.trim()

      const blob = new Blob([content], { type: 'text/plain;charset=utf-8' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${invoice.number}.txt`
      link.click()
      URL.revokeObjectURL(url)
      
      showSuccess('Rechnung wurde heruntergeladen.')
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
    formatDate(dateStr) {
      if (!dateStr) return '-'
      try {
        return new Date(dateStr).toLocaleDateString('de-DE')
      } catch {
        return dateStr
      }
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount || 0)
    }
  }
}
</script>

<style scoped>
.invoice-generator {
  padding: 20px;
  max-width: 900px;
}

.invoice-generator h3 {
  margin-bottom: 8px;
}

.description {
  color: var(--color-text-maxcontrast);
  margin-bottom: 24px;
}

.generator-sections section {
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  padding: 20px;
  margin-bottom: 20px;
}

section h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  color: var(--color-main-text);
}

.filter-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.empty-notice {
  text-align: center;
  padding: 30px;
  color: var(--color-text-maxcontrast);
}

.empty-notice p {
  margin-top: 10px;
}

.select-all {
  padding: 10px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  margin-bottom: 10px;
}

.application-item {
  padding: 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  margin-bottom: 8px;
  transition: all 0.2s;
}

.application-item:hover {
  border-color: var(--color-primary);
}

.application-item.selected {
  background: var(--color-primary-light);
  border-color: var(--color-primary);
}

.app-info {
  display: flex;
  flex-direction: column;
  margin-left: 8px;
}

.app-meta {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.setting-group {
  display: flex;
  flex-direction: column;
}

.setting-group.full-width {
  grid-column: span 2;
}

.setting-group label {
  margin-bottom: 6px;
  font-weight: 500;
  font-size: 13px;
}

.nc-textarea {
  width: 100%;
  padding: 10px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  font-family: inherit;
  resize: vertical;
}

.summary-box {
  display: flex;
  gap: 32px;
  padding: 16px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  margin-bottom: 16px;
}

.summary-item {
  display: flex;
  flex-direction: column;
}

.summary-label {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.summary-value {
  font-size: 20px;
  font-weight: 600;
}

.generate-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.invoices-table {
  width: 100%;
  border-collapse: collapse;
}

.invoices-table th,
.invoices-table td {
  padding: 10px 12px;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

.invoices-table th {
  background: var(--color-background-dark);
  font-weight: 600;
}
</style>
