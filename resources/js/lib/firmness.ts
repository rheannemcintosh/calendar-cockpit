import type { FirmnessValue } from '@/types';

// How a calendar item's firmness changes its fill, as inline styles. Confirmed
// keeps the fill it has always had, so it has none here. Tentative is a
// lighter, see-through tint of the colour. Idea is diagonal stripes of it: a
// background, so it can't be mistaken for the dashed border of Set aside.
// `tint` is how strongly, in percent, the view tints a confirmed item.
export function firmnessFill(
    firmness: FirmnessValue,
    colour: string | null,
    tint: number,
): Record<string, string> | undefined {
    // Uncategorised calendar items are tinted grey, and more faintly.
    const base = colour ?? 'var(--muted-foreground)';
    const strength = colour ? tint : tint * 0.4;

    if (firmness === 'tentative') {
        return {
            backgroundColor: `color-mix(in srgb, ${base} ${strength / 2}%, transparent)`,
        };
    }

    if (firmness === 'idea') {
        const stripe = `color-mix(in srgb, ${base} ${strength * 0.7}%, var(--background))`;

        return {
            backgroundColor: 'var(--background)',
            backgroundImage: `repeating-linear-gradient(135deg, ${stripe} 0 4px, transparent 4px 10px)`,
        };
    }

    return undefined;
}

// Muted text loses contrast on a tinted fill, so on tentative and idea
// calendar items it is this darker grey instead, as is an idea's title.
export const FIRMNESS_GREY = 'text-foreground/70';

export function secondaryText(firmness: FirmnessValue): string {
    return firmness === 'confirmed' ? 'text-muted-foreground' : FIRMNESS_GREY;
}

export function titleText(firmness: FirmnessValue): string | undefined {
    return firmness === 'idea' ? FIRMNESS_GREY : undefined;
}
