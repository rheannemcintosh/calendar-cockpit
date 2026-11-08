// The values of App\Enums\Firmness.
export type FirmnessValue = 'confirmed' | 'tentative' | 'idea';

// A firmness as offered on a form, from App\Enums\Firmness::options().
export type FirmnessOption = {
    value: FirmnessValue;
    label: string;
    hint: string;
};
