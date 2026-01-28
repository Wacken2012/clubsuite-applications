<template>
  <NcContent app-name="clubsuite-applications">
    <NcAppContent>
      <div class="app-applications">
        <header class="app-header">
          <h2>Mitgliedsanträge & Abschlussrechnungen</h2>
        </header>

        <NcAppNavigationList>
          <NcAppNavigationItem 
            name="Anträge"
            :active="activeTab === 'list'"
            @click="activeTab = 'list'"
          >
            <template #icon>
              <FormatListBulleted :size="20" />
            </template>
          </NcAppNavigationItem>
          <NcAppNavigationItem 
            name="Neuer Antrag"
            :active="activeTab === 'form'"
            @click="activeTab = 'form'; editingApplication = null"
          >
            <template #icon>
              <Plus :size="20" />
            </template>
          </NcAppNavigationItem>
          <NcAppNavigationItem 
            name="Rechnungsgenerator"
            :active="activeTab === 'invoices'"
            @click="activeTab = 'invoices'"
          >
            <template #icon>
              <FileDocument :size="20" />
            </template>
          </NcAppNavigationItem>
        </NcAppNavigationList>

        <div class="app-content-main">
          <ApplicationList 
            v-if="activeTab === 'list'"
            @edit="onEdit"
            @refresh="refreshKey++"
            :key="refreshKey"
          />
          <ApplicationForm 
            v-if="activeTab === 'form'"
            :application="editingApplication"
            @saved="onSaved"
            @cancel="activeTab = 'list'"
          />
          <InvoiceGenerator 
            v-if="activeTab === 'invoices'"
          />
        </div>
      </div>
    </NcAppContent>
  </NcContent>
</template>

<script>
import { NcContent, NcAppContent, NcAppNavigationList, NcAppNavigationItem } from '@nextcloud/vue'
import ApplicationForm from './components/ApplicationForm.vue'
import ApplicationList from './components/ApplicationList.vue'
import InvoiceGenerator from './components/InvoiceGenerator.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import FormatListBulleted from 'vue-material-design-icons/FormatListBulleted.vue'
import FileDocument from 'vue-material-design-icons/FileDocument.vue'

export default {
  name: 'App',
  components: {
    NcContent,
    NcAppContent,
    NcAppNavigationList,
    NcAppNavigationItem,
    ApplicationForm,
    ApplicationList,
    InvoiceGenerator,
    Plus,
    FormatListBulleted,
    FileDocument
  },
  data() {
    return {
      activeTab: 'list',
      editingApplication: null,
      refreshKey: 0
    }
  },
  methods: {
    onEdit(application) {
      this.editingApplication = application
      this.activeTab = 'form'
    },
    onSaved() {
      this.editingApplication = null
      this.activeTab = 'list'
      this.refreshKey++
    }
  }
}
</script>

<style scoped>
.app-applications {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.app-header {
  margin-bottom: 20px;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 15px;
}

.app-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.app-content-main {
  margin-top: 20px;
}
</style>
