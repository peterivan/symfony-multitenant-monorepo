import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';

import { createVuetify } from 'vuetify';

/**
 * Vuetify is the DEFAULT, single UI component foundation for all surfaces
 * (ADR-004). A competing primary UI framework requires a superseding ADR.
 */
export const vuetify = createVuetify({
    icons: {
        defaultSet: 'mdi',
    },
    defaults: {
        // Cross-surface interaction defaults so shared components behave
        // consistently across Back Office and tenant-facing surfaces.
        VBtn: { variant: 'flat' },
    },
    theme: {
        defaultTheme: 'light',
    },
});
