<script setup lang="ts">
/**
 * Shared, cross-surface application layout (ADR-004). Used by both Back Office and
 * tenant-facing surfaces; preserves consistent accessibility (landmarks, skip
 * link), responsive behavior (Vuetify grid), and interaction patterns.
 *
 * Not boundary-specific: any surface may consume it.
 */
defineProps<{
    title: string;
}>();
</script>

<template>
  <v-app>
    <a
      href="#main-content"
      class="skip-link"
    >Skip to main content</a>
    <v-app-bar
      role="banner"
      density="comfortable"
    >
      <v-app-bar-title>{{ title }}</v-app-bar-title>
      <template #append>
        <slot name="bar-actions" />
      </template>
    </v-app-bar>
    <v-main>
      <v-container
        id="main-content"
        tag="main"
        role="main"
        fluid
      >
        <slot />
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
.skip-link {
    position: absolute;
    left: -9999px;
    top: 0;
    z-index: 100;
}
.skip-link:focus {
    left: 0;
}
</style>
