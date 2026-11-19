# Starter Categories

This document sets out the starter categories and sub-categories that Calendar Cockpit offers a new user. They are a sensible starting point rather than a fixed system: a person can rename, recolour, add or remove any of them once they are in their account.

Each category has a name and a colour. Each sub-category belongs to one category and has an emoji, a name and a default time type, which a calendar item in that sub-category starts with and can change.

## Time Types

| Time Type   | Label     | Meaning                                        |
| ----------- | --------- | ---------------------------------------------- |
| Exact       | Exact     | Starts and ends at these times.                |
| Window      | Between   | A booked slot. It could happen any time in it. |
| Block       | Set aside | Time set aside for this.                       |
| Approximate | About     | Roughly these times.                           |

## Categories

Each colour is one of the preset colours in the app's colour picker.

| Category       | Colour | Hex       |
| -------------- | ------ | --------- |
| Work           | Blue   | `#3B82F6` |
| Getting Around | Grey   | `#6B7280` |
| Travel         | Cyan   | `#06B6D4` |
| Health         | Red    | `#EF4444` |
| Active         | Lime   | `#84CC16` |
| Social         | Pink   | `#EC4899` |
| Food & Drink   | Orange | `#F97316` |
| Going Out      | Violet | `#8B5CF6` |
| Downtime       | Yellow | `#EAB308` |
| Home & Errands | Brown  | `#92400E` |
| Money          | Green  | `#22C55E` |

## Sub-Categories

### Work

| Emoji | Sub-Category      | Default Time Type |
| ----- | ----------------- | ----------------- |
| 🏢    | In Office         | Block             |
| 🏠    | Working from Home | Block             |
| 🎯    | Focus Time        | Block             |
| 🌴    | Annual Leave      | Block             |
| 📌    | Work Milestones   | Block             |

### Getting Around

| Emoji | Sub-Category | Default Time Type |
| ----- | ------------ | ----------------- |
| 🚶    | Walk         | Approximate       |
| 🚌    | Bus          | Approximate       |
| 🚕    | Taxi         | Approximate       |
| 🚇    | Tube         | Approximate       |
| 🚆    | Train        | Exact             |

### Travel

| Emoji | Sub-Category  | Default Time Type |
| ----- | ------------- | ----------------- |
| ✨    | Trips         | Block             |
| ✈️    | Flights       | Exact             |
| 🏨    | Hotels        | Window            |
| 🚄    | Booked Trains | Exact             |
| 🚌    | Coaches       | Exact             |

### Health

| Emoji | Sub-Category           | Default Time Type |
| ----- | ---------------------- | ----------------- |
| 🩺    | Doctors                | Exact             |
| 🏥    | Hospital & Specialists | Exact             |
| 😬    | Dentist                | Exact             |
| 👓    | Optician               | Exact             |
| 🦴    | Physio                 | Exact             |
| 💊    | Prescriptions          | Approximate       |

### Active

| Emoji | Sub-Category   | Default Time Type |
| ----- | -------------- | ----------------- |
| 🏋️    | Gym            | Approximate       |
| 🧘    | Classes        | Exact             |
| 🎽    | Playing Sports | Exact             |
| 🏃    | Running        | Approximate       |
| 🥾    | Leisure Walks  | Approximate       |

### Social

| Emoji | Sub-Category     | Default Time Type |
| ----- | ---------------- | ----------------- |
| 👯    | Seeing Friends   | Block             |
| 👨‍👩‍👧    | Family           | Block             |
| 👋    | Friends Visiting | Block             |
| 🛏️    | Houseguests      | Block             |
| 🎉    | Parties          | Approximate       |
| 💒    | Weddings         | Exact             |
| 🎂    | Birthdays        | Block             |
| 💍    | Anniversaries    | Block             |

### Food & Drink

| Emoji | Sub-Category | Default Time Type |
| ----- | ------------ | ----------------- |
| 🍽️    | Restaurants  | Exact             |
| ☕    | Cafes        | Approximate       |
| 🍸    | Bars         | Approximate       |

### Going Out

| Emoji | Sub-Category | Default Time Type |
| ----- | ------------ | ----------------- |
| 🎧    | Sets         | Exact             |
| 🎪    | Festivals    | Block             |
| 🏟️    | Live Sport   | Exact             |
| 🎭    | Theatre      | Exact             |
| 🍿    | Cinema       | Exact             |

### Downtime

| Emoji | Sub-Category  | Default Time Type |
| ----- | ------------- | ----------------- |
| 📺    | Watching      | Exact             |
| 🎨    | Hobbies       | Approximate       |
| 📚    | Learning      | Block             |
| 💻    | Side Projects | Block             |

### Home & Errands

| Emoji | Sub-Category     | Default Time Type |
| ----- | ---------------- | ----------------- |
| 🛍️    | Shopping         | Approximate       |
| 🛒    | Groceries        | Approximate       |
| 📦    | Deliveries       | Window            |
| 🔧    | Tradespeople     | Window            |
| 🧹    | Chores           | Approximate       |
| 💇    | Haircut & Beauty | Exact             |

### Money

| Emoji | Sub-Category | Default Time Type |
| ----- | ------------ | ----------------- |
| 💸    | Bills        | Block             |
| 🤑    | Pay Day      | Block             |
| 🔁    | Renewals     | Block             |

## Notes

- **Items that last all day.** Annual Leave, Work Milestones, Friends Visiting, Houseguests, Birthdays, Anniversaries, Bills, Pay Day and Renewals usually cover a whole day or several days. Calendar Cockpit has no all-day items yet, so these default to Block, set aside for the whole day.
- **Time within a bigger plan.** Seeing Friends defaults to Block so it can hold the whole time agreed with a friend, such as 16:00 to 21:00. Anything planned inside it, such as a restaurant booked for 17:30 to 19:00 or a walk or a bar afterwards, is its own item with its own sub-category and time type, and shows alongside the block in the day view.
- **Getting Around and Travel.** Getting Around is for getting from place to place locally. Travel is for trips away and the journeys booked for them, so a pre-booked train is a Booked Train and a local one is a Train.
