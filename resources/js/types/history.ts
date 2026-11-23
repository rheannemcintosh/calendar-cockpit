// A field that changed in an entry of a history, by the name the app uses for
// it. Old or new is null when it had no value.
export type HistoryChange = {
    field: string;
    label: string;
    old: string | null;
    new: string | null;
};

// Where a change came from when something else made it: a calendar item's
// category or sub-category being changed or deleted. The name is the one it
// had just after the change.
export type HistorySource = {
    type: 'category' | 'sub_category';
    action: 'updated' | 'deleted';
    name: string;
};

// A change made to something, or it being created, which lists no changes.
// When it was made is in the user's timezone, e.g. "21 Nov 2026, 14:05", and
// as ISO 8601 in `datetime`.
export type HistoryEntry = {
    key: string;
    event: 'created' | 'updated';
    at: string;
    datetime: string;
    changes: HistoryChange[];
    // Null, or left out, when the change was made to it directly.
    source?: HistorySource | null;
};
