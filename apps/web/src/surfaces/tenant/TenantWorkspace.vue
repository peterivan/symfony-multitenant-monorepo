<script setup lang="ts">
import { storeToRefs } from 'pinia';

import BaseLayout from '@/shared/layouts/BaseLayout.vue';
import CapabilityGate from '@/shared/components/CapabilityGate.vue';
import { useTenantContextStore } from '@/shared/stores/tenantContext';

/**
 * Example tenant-facing surface. It consumes the shared foundation and FAILS
 * CLOSED: tenant-scoped content is shown only when the backend resolved a tenant.
 */
const tenantContext = useTenantContextStore();
const { activeTenant, tenantScopedReady } = storeToRefs(tenantContext);
</script>

<template>
  <BaseLayout :title="activeTenant?.displayName ?? 'Tenant'">
    <template v-if="tenantScopedReady">
      <p>Workspace for {{ activeTenant?.slug }}.</p>
      <CapabilityGate capability="tenant.documents.create">
        <v-btn>New document</v-btn>
      </CapabilityGate>
    </template>
    <v-alert
      v-else
      type="warning"
      variant="tonal"
      role="status"
    >
      No tenant context resolved by the backend. Tenant-scoped data and actions are withheld.
    </v-alert>
  </BaseLayout>
</template>
