import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from '@/App.vue';
import { ApiClient } from '@/shared/api/client';
import { SessionApi } from '@/shared/api/session';
import { hydrateFromBackend } from '@/shared/bootstrap';
import { vuetify } from '@/plugins/vuetify';

const app = createApp(App);

// Pinia is the single default shared client-side store (ADR-004).
app.use(createPinia());
app.use(vuetify);

app.mount('#app');

// Hydrate all client state from the backend source of truth after mount so the
// app shell can render its loading state first.
const sessionApi = new SessionApi(new ApiClient());
void hydrateFromBackend(sessionApi);
