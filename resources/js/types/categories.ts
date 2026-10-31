// A category as listed on the create sub-category form.

export type CategoryOption = {
    id: number;
    name: string;
    colour: string;
};

import type { TimeTypeValue } from './time-types';

// A sub-category as listed under its category on the categories page.
export type SubCategoryListItem = {
    id: number;
    emoji: string;
    name: string;
};

// A sub-category as shown on the edit sub-category form.
export type EditableSubCategory = SubCategoryListItem & {
    defaultTimeType: TimeTypeValue;
};

// A category as listed on the categories page, with its sub-categories in
// alphabetical order.
export type CategoryListItem = CategoryOption & {
    subCategories: SubCategoryListItem[];
};

// A sub-category on the categories page, with how many calendar items use it.
export type ManagedSubCategory = SubCategoryListItem & {
    calendarItemsCount: number;
};

// A category on the categories page, with how many calendar items are in it
// through its sub-categories.
export type ManagedCategory = CategoryOption & {
    calendarItemsCount: number;
    subCategories: ManagedSubCategory[];
};
