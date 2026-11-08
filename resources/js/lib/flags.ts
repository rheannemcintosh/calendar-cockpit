import { Lock, PoundSterling, Star } from '@lucide/vue';
import type { Component } from 'vue';
import type { FlagIconKey } from '@/types';

// The Lucide icon each App\Enums\FlagIcon shows.
export const flagIcons: Record<FlagIconKey, Component> = {
    'pound-sterling': PoundSterling,
    lock: Lock,
    star: Star,
};
