// A category as listed on the create sub-category form.

export type CategoryOption = {
    id: number;
    name: string;
    colour: string;
};

// A sub-category as listed under its category on the categories page.
export type SubCategoryListItem = {
    id: number;
    emoji: string;
    name: string;
};

// A category as listed on the categories page, with its sub-categories in
// alphabetical order.
export type CategoryListItem = CategoryOption & {
    subCategories: SubCategoryListItem[];
};
