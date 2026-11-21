// From App\Support\Timestamps::of(): when something was created and last
// updated, e.g. "21 Nov 2026, 14:05", in the user's timezone. Updated is null
// when it has not been changed since it was created.
export type Timestamps = {
    created: string;
    updated: string | null;
};
