<script setup lang="ts">
import { storeToRefs } from 'pinia';

import BaseLayout from '@/shared/layouts/BaseLayout.vue';
import CapabilityGate from '@/shared/components/CapabilityGate.vue';
import { useSessionStore } from '@/shared/stores/session';

/**
 * Example Back Office surface. Central-context only — it does not read or present
 * tenant context. Authorization affordances are presentation-only; the backend
 * `/bo` firewall (ADR-002/007) remains the access boundary.
 */
const { isAuthenticated } = storeToRefs(useSessionStore());
</script>

<template>
  <BaseLayout title="Back Office">
    <template v-if="isAuthenticated">
      <p>Platform operations console.</p>
      <CapabilityGate capability="backoffice.tenant.suspend">
        <v-btn color="warning">
          Suspend tenant
        </v-btn>
      </CapabilityGate>
    </template>
    <v-alert
      v-else
      type="info"
      variant="tonal"
      role="status"
    >
      Sign in as a platform operator to continue.
    </v-alert>
  </BaseLayout>
</template>
