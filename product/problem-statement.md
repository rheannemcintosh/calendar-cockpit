# Problem Statement

## The Challenge

### Who this is for

People who plan their personal life in a digital calendar. Their working day usually lives in a separate calendar run by their employer, but everything outside it, from health appointments, deliveries and errands to exercise, time with family and friends, study, and side projects, sits in a calendar of their own. They plan ahead deliberately, and they use that calendar as the picture of where their time goes, so they need it to tell them more than when something starts and ends.

### The problem

A calendar is meant to show how a day will actually be spent. In practice, it flattens everything into the same shape, so every day looks more rigid than it really is.

**Every event looks like a fixed appointment.** Calendars have one kind of event: something that starts at one time and ends at another. At most, an event can be shown as busy, free, tentative or out of office, which describes availability to other people rather than the nature of the time itself. A 10:00 meeting, a delivery that will arrive "between 8 and 12", an afternoon blocked out for focused work, and a coffee at "roughly 2" are very different commitments, and they all look the same. Nothing shows which times are fixed, which are a slot something could happen anywhere within, which are time protected for a purpose, and which are only a rough estimate. It is unclear which items are concrete and which can move, so when plans change there is no telling what can be shuffled and what must stay put. People either leave the flexible things out, or put them in and watch the whole day harden into fixed appointments.

**Time isn't held in the right-sized pieces.** Plans don't fit neatly into the two shapes a calendar offers, an exact slot or an all-day event. An all-day entry for "dentist sometime in the afternoon" says too little, and an exact 14:00 to 15:00 entry says something that isn't true. The calendar ends up either too vague to plan around or too precise to trust.

**There's no way to see which part of life something belongs to.** Colour is the only tool a calendar offers for this, and a colour on its own means nothing. It has no name, so what each colour stands for lives only in the person's head, and the more colours there are, the harder they are to tell apart and remember. For years, a single calendar offered too few colours to cover every part of a person's life, so the way to get more was to create a separate calendar for each area, splitting one life across many calendars that each had to be shared and managed separately. Neither more colours nor more calendars offer a finer level of detail, such as telling a physio appointment from a GP appointment within health, so people fall back on typing an emoji into each event's name to mark its sub-category, and keeping track of which emoji means what.

**There's no way to say what kind of commitment something is.** Some events are pre-paid, some can't be cancelled, some are important, some are tentative, and some are only an idea for now. These details decide what can be moved or dropped when plans change, yet a calendar has nowhere to record them. People end up adding a second emoji to the event name, such as one for pre-paid and another for an idea, alongside the sub-category emoji already there, or they keep it in their head.

### Why existing options fall short

Google Calendar limited events to 11 colours until 2026, when it added 24 default colours and up to 200 custom ones in answer to a long-standing request from users [[1]](https://workspaceupdates.googleblog.com/2026/06/custom-event-colors-in-google-calendar.html). Before that, getting more colours meant splitting a life across multiple calendars, each of which had to be shared and managed separately. Even now, the colours have no names and no sub-categories, so what each one means is still up to the person to remember. Outlook offers a wider range of colours, but many of them are hard to tell apart, and it is built for corporate use, around meetings and availability at work rather than a person's life outside it. Both describe events mainly in terms of availability: Outlook as free, working elsewhere, tentative, busy or out of office, and a personal Google Calendar as busy or free, with focus time and out of office events kept for paid work and school accounts. None of this is a full enough range to block out focus time in a personal calendar, show a rough estimate, or mark a window something will happen within. Task and time-blocking tools are built around productivity at work, scheduling to-do items into free slots. None of them let a person say, for each piece of time, what it is, which part of life it belongs to, how precise its times are, and what kind of commitment it is.

### What it costs

The cost is a calendar that can't be trusted at a glance. People misjudge how much room their day really has, because windows and rough estimates look like fixed commitments, or they plan around gaps that aren't really free. To make up for what the calendar lacks, they build and maintain their own systems by hand: colours assigned to categories and remembered, emojis typed into event names to mark sub-categories, and more emojis to flag what is pre-paid, important or tentative. These conventions take effort to keep consistent, mean nothing to the calendar itself, and are easily lost when the calendar is shared. And without a reliable sense of which part of life each piece of time belongs to, the calendar can't show whether time is being spent the way the person intends, which is the very thing they keep it for.

## References

[[1]](https://workspaceupdates.googleblog.com/2026/06/custom-event-colors-in-google-calendar.html) Google Workspace Updates, *Custom event colors in Google Calendar*, June 2026. https://workspaceupdates.googleblog.com/2026/06/custom-event-colors-in-google-calendar.html

## Author's Note

This problem statement is grounded in first-hand experience of planning a whole life in digital calendars. Google Calendar's colours were the most useful feature, but getting enough of them meant creating multiple calendars, which made sharing difficult, while a single calendar never had enough colours to go round, and the colours themselves never said what they stood for. Outlook felt too corporate, and the busy, free, tentative and out of office statuses in both were never a full enough range to block out focus time or show that a block was only a rough estimate. To fill the gap, I built my own category system for each calendar: categories mapped to the colours available on one calendar, sub-categories marked with emojis in event names, and secondary emojis showing whether an event was pre-paid, non-cancellable, important, tentative or just an idea.
