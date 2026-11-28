// From App\Http\Controllers\InsightsController::index(). Each created date is
// the day it was created, e.g. "21 Nov 2026", in the user's timezone.

// The user's first category, sub-category and calendar item, each null until
// they have one.
export type InsightsFirsts = {
    category: { name: string; colour: string; created: string } | null;
    subCategory: {
        emoji: string;
        name: string;
        categoryName: string;
        created: string;
    } | null;
    calendarItem: InsightsCalendarItem | null;
};

// A calendar item with when it was created.
export type InsightsCalendarItem = { title: string; created: string };

// A sub-category with when it was created, and the first calendar item
// created in it, or null when it has none.
export type InsightsSubCategory = {
    id: number;
    emoji: string;
    name: string;
    created: string;
    firstCalendarItem: InsightsCalendarItem | null;
};

// A category with when it was created, the first calendar item created in
// any of its sub-categories, and its sub-categories oldest first.
export type InsightsCategory = {
    id: number;
    name: string;
    colour: string;
    created: string;
    firstCalendarItem: InsightsCalendarItem | null;
    subCategories: InsightsSubCategory[];
};
