<script setup lang="ts">
import { computed } from 'vue';
import { storeToRefs } from 'pinia';

import BackOfficeConsole from '@/surfaces/back-office/BackOfficeConsole.vue';
import TenantWorkspace from '@/surfaces/tenant/TenantWorkspace.vue';
import { useSessionStore } from '@/shared/stores/session';

/**
 * Root component. The active surface is chosen from the BACKEND-provided auth
 * surface — the frontend does not decide the surface itself (ADR-004).
 */
const { auth, loaded } = storeToRefs(useSessionStore());

const surface = computed(() => auth.value.surface);
</script>

<template>
  <BackOfficeConsole v-if="loaded && surface === 'back_office'" />
  <TenantWorkspace v-else-if="loaded && surface === 'tenant_facing'" />
  <BackOfficeConsole v-else-if="loaded && surface === 'central_platform'" />
  <v-app v-else>
    <v-main>
      <v-container
        role="status"
        aria-busy="true"
      >
        Loading…
      </v-container>
    </v-main>
  </v-app>
</template>
