// One match from App\Support\LocationSearch, returned by the location search.
export type LocationMatch = {
    id: string;
    name: string;
    // Empty when the place has no address.
    address: string;
    latitude: number;
    longitude: number;
};
