<template>
  <div class="application-form">
    <h3>{{ isEditing ? 'Antrag bearbeiten' : 'Neuen Antrag erstellen' }}</h3>
    
    <form @submit.prevent="submitForm">
      <div class="form-group">
        <label for="type">Antragstyp *</label>
        <NcSelect
          v-model="form.type"
          :options="applicationTypes"
          label="label"
          track-by="value"
          placeholder="Antragstyp wählen..."
          :reduce="option => option.value"
        />
      </div>

      <div class="form-group">
        <label for="title">Titel / Bezeichnung *</label>
        <NcTextField
          v-model="form.title"
          label="Titel"
          placeholder="z.B. Mitgliedsantrag Max Mustermann"
          :error="errors.title"
          required
        />
        <span v-if="errors.title" class="error-text">{{ errors.title }}</span>
      </div>

      <div class="form-group">
        <label for="memberId">Mitglieds-ID (optional)</label>
        <NcTextField
          v-model="form.memberId"
          label="Mitglieds-ID"
          placeholder="Falls vorhanden"
          type="number"
        />
      </div>

      <div class="form-group" v-if="form.type === 'membership'">
        <h4>Mitgliedsdaten</h4>
        <div class="form-row">
          <NcTextField v-model="formData.firstName" label="Vorname" placeholder="Vorname" />
          <NcTextField v-model="formData.lastName" label="Nachname" placeholder="Nachname" />
        </div>
        <div class="form-row">
          <NcTextField v-model="formData.email" label="E-Mail" placeholder="E-Mail" type="email" />
          <NcTextField v-model="formData.phone" label="Telefon" placeholder="Telefon" />
        </div>
        <div class="form-row">
          <NcTextField v-model="formData.street" label="Straße" placeholder="Straße + Hausnr." />
          <NcTextField v-model="formData.zip" label="PLZ" placeholder="PLZ" />
          <NcTextField v-model="formData.city" label="Ort" placeholder="Ort" />
        </div>
        <div class="form-row">
          <NcTextField v-model="formData.birthDate" label="Geburtsdatum" type="date" />
        </div>
      </div>

      <div class="form-group" v-if="form.type === 'fee_reduction'">
        <h4>Beitragsermäßigung</h4>
        <div class="form-row">
          <NcTextField v-model="formData.reason" label="Begründung" placeholder="Grund für Ermäßigung" />
          <NcTextField v-model="formData.requestedAmount" label="Gewünschter Beitrag (€)" type="number" step="0.01" />
        </div>
        <div class="form-group">
          <label>Nachweise hochgeladen?</label>
          <NcCheckboxRadioSwitch v-model="formData.documentsProvided" type="checkbox">
            Ja, Nachweise liegen vor
          </NcCheckboxRadioSwitch>
        </div>
      </div>

      <div class="form-group" v-if="form.type === 'resignation'">
        <h4>Kündigung</h4>
        <div class="form-row">
          <NcTextField v-model="formData.resignationDate" label="Kündigungsdatum" type="date" />
          <NcTextField v-model="formData.reason" label="Kündigungsgrund (optional)" placeholder="Optional" />
        </div>
      </div>

      <div class="form-group" v-if="form.type === 'other'">
        <h4>Sonstiger Antrag</h4>
        <div class="form-group">
          <label>Beschreibung</label>
          <textarea 
            v-model="formData.description" 
            class="nc-textarea"
            rows="4"
            placeholder="Beschreiben Sie Ihren Antrag..."
          ></textarea>
        </div>
      </div>

      <div class="form-group" v-if="isEditing">
        <label>Status</label>
        <NcSelect
          v-model="form.status"
          :options="statusOptions"
          label="label"
          track-by="value"
          :reduce="option => option.value"
        />
      </div>

      <div class="form-actions">
        <NcButton type="tertiary" @click="$emit('cancel')">
          <template #icon><Close :size="20" /></template>
          Abbrechen
        </NcButton>
        <NcButton type="primary" native-type="submit" :disabled="loading">
          <template #icon>
            <NcLoadingIcon v-if="loading" :size="20" />
            <Check v-else :size="20" />
          </template>
          {{ isEditing ? 'Speichern' : 'Antrag erstellen' }}
        </NcButton>
      </div>
    </form>
  </div>
</template>

<script>
import { NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch, NcLoadingIcon } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import Check from 'vue-material-design-icons/Check.vue'
import Close from 'vue-material-design-icons/Close.vue'

export default {
  name: 'ApplicationForm',
  components: {
    NcButton,
    NcTextField,
    NcSelect,
    NcCheckboxRadioSwitch,
    NcLoadingIcon,
    Check,
    Close
  },
  props: {
    application: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      loading: false,
      form: {
        title: '',
        type: 'membership',
        memberId: null,
        status: 'pending'
      },
      formData: {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        street: '',
        zip: '',
        city: '',
        birthDate: '',
        reason: '',
        requestedAmount: null,
        documentsProvided: false,
        resignationDate: '',
        description: ''
      },
      errors: {},
      applicationTypes: [
        { value: 'membership', label: 'Mitgliedsantrag' },
        { value: 'fee_reduction', label: 'Beitragsermäßigung' },
        { value: 'resignation', label: 'Kündigung' },
        { value: 'other', label: 'Sonstiger Antrag' }
      ],
      statusOptions: [
        { value: 'pending', label: 'Ausstehend' },
        { value: 'approved', label: 'Genehmigt' },
        { value: 'rejected', label: 'Abgelehnt' }
      ]
    }
  },
  computed: {
    isEditing() {
      return this.application !== null
    }
  },
  watch: {
    application: {
      immediate: true,
      handler(val) {
        if (val) {
          this.form = {
            title: val.title || '',
            type: val.type || 'membership',
            memberId: val.member_id || val.memberId || null,
            status: val.status || 'pending'
          }
          if (val.data_json) {
            try {
              const data = typeof val.data_json === 'string' ? JSON.parse(val.data_json) : val.data_json
              this.formData = { ...this.formData, ...data }
            } catch (e) {
              console.error('Error parsing data_json:', e)
            }
          }
        } else {
          this.resetForm()
        }
      }
    }
  },
  methods: {
    resetForm() {
      this.form = {
        title: '',
        type: 'membership',
        memberId: null,
        status: 'pending'
      }
      this.formData = {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        street: '',
        zip: '',
        city: '',
        birthDate: '',
        reason: '',
        requestedAmount: null,
        documentsProvided: false,
        resignationDate: '',
        description: ''
      }
      this.errors = {}
    },
    validate() {
      this.errors = {}
      if (!this.form.title || this.form.title.trim() === '') {
        this.errors.title = 'Titel ist erforderlich'
      }
      if (!this.form.type) {
        this.errors.type = 'Antragstyp ist erforderlich'
      }
      return Object.keys(this.errors).length === 0
    },
    async submitForm() {
      if (!this.validate()) {
        showError('Bitte füllen Sie alle Pflichtfelder aus.')
        return
      }

      this.loading = true
      try {
        const payload = {
          title: this.form.title,
          type: this.form.type,
          memberId: this.form.memberId,
          status: this.form.status,
          data: this.formData
        }

        let response
        if (this.isEditing) {
          const url = generateUrl(`/apps/clubsuite-applications/api/applications/${this.application.id}`)
          response = await axios.put(url, payload)
          showSuccess('Antrag wurde aktualisiert.')
        } else {
          const url = generateUrl('/apps/clubsuite-applications/api/applications')
          response = await axios.post(url, payload)
          showSuccess('Antrag wurde erstellt.')
        }

        this.$emit('saved', response.data)
        this.resetForm()
      } catch (error) {
        console.error('Error saving application:', error)
        showError(error.response?.data?.error || 'Fehler beim Speichern des Antrags.')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.application-form {
  max-width: 800px;
  padding: 20px;
  background: var(--color-main-background);
  border-radius: var(--border-radius-large);
}

.application-form h3 {
  margin-bottom: 20px;
  font-size: 20px;
}

.application-form h4 {
  margin: 20px 0 10px;
  font-size: 16px;
  color: var(--color-text-maxcontrast);
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 5px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
}

.form-row {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.form-row > * {
  flex: 1;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.error-text {
  color: var(--color-error);
  font-size: 12px;
  margin-top: 4px;
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

.nc-textarea:focus {
  border-color: var(--color-primary);
  outline: none;
}
</style>
