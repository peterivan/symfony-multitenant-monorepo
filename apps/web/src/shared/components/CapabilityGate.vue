<script setup lang="ts">
import { computed } from 'vue';
import { storeToRefs } from 'pinia';

import { can } from '@/shared/authorization/can';
import { useSessionStore } from '@/shared/stores/session';
import type { Capability } from '@/shared/types/backend';

/**
 * Presentation-only gate (ADR-004): hides/disables affordances based on a
 * backend-provided capability. This is NOT an access boundary — the backend
 * enforces authorization and will reject disallowed actions regardless of this.
 */
const props = defineProps<{
    capability: Capability;
    /** When true, render disabled instead of hidden. */
    disableInsteadOfHide?: boolean;
}>();

const { capabilities } = storeToRefs(useSessionStore());
const allowed = computed(() => can(capabilities.value, props.capability));
</script>

<template>
  <template v-if="allowed">
    <slot />
  </template>
  <template v-else-if="disableInsteadOfHide">
    <div
      :aria-disabled="true"
      class="capability-gate--disabled"
    >
      <slot />
    </div>
  </template>
</template>

<style scoped>
.capability-gate--disabled {
    opacity: 0.5;
    pointer-events: none;
}
</style>
