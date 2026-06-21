import { describe, expect, it } from 'vitest';

import { CLIENT_CHECK_IS_NON_AUTHORITATIVE, can, canAll } from '@/shared/authorization/can';

describe('presentation-only authorization', () => {
    it('reflects backend-granted capabilities', () => {
        const granted = ['a', 'b'];
        expect(can(granted, 'a')).toBe(true);
        expect(can(granted, 'c')).toBe(false);
        expect(canAll(granted, ['a', 'b'])).toBe(true);
        expect(canAll(granted, ['a', 'c'])).toBe(false);
    });

    it('is documented as non-authoritative', () => {
        // A guard so this intent is not silently flipped into an access decision.
        expect(CLIENT_CHECK_IS_NON_AUTHORITATIVE).toBe(true);
    });
});
