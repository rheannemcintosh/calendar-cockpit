# Personas and Job-to-be-Done

This document defines the primary user types for Calendar Cockpit and the jobs they need the product to accomplish. It establishes a shared understanding of who the product serves and the situations in which they use it, to inform design, prioritisation, and development decisions.

Two personas are defined. Both plan their personal life in a digital calendar, with their working day kept in a separate calendar run by their employer. They differ in how much their calendar already does for them, and each represents a distinct set of needs the product must serve. Following the personas, a set of jobs-to-be-done describes the specific moments a user reaches for the app.

## Personas

### Persona 1 — The System Builder

A long-term, deliberate planner who has outgrown what their calendar offers, representing the core user of the product.

**Context.** Has kept a personal calendar for years and puts everything in it: health appointments, deliveries and errands, exercise, time with family and friends, study, and side projects. Has tried Google Calendar and Outlook. To make up for what they lack, has built their own system by hand: categories mapped to colours, sub-categories marked with an emoji in each event name, and a second emoji to show whether an event is pre-paid, non-cancellable, important, tentative or just an idea. In the past has split their life across several calendars to get more colours, and found each one had to be shared and managed separately.

**Goal.** See at a glance what each piece of their day is, which part of life it belongs to, and how firm it is, without having to maintain a system of colours and emojis that the calendar itself doesn't understand.

**Constraints.** Already has a detailed mental model of their own categories and sub-categories, so the product must fit that model rather than force a fixed one. Their system took a long time to build and needs to carry over with little effort. Has no interest in work productivity features; this calendar is for life outside work.

**Biggest frustration.** Every event looks like a fixed appointment. A delivery window, a rough plan for "around 2" and an afternoon set aside for focused work all look the same as a 10:00 appointment, so when plans change there is no telling what can be shuffled and what must stay put.

**Product needs.** Time types that say how precise each piece of time is: exact, a window, a protected block, or approximate. Named categories and sub-categories with their own colours, replacing the colour and emoji conventions. A way to record what kind of commitment something is. A view that makes all of this readable at a glance.

### Persona 2 — The Head Planner

A busy person who keeps most of their personal plans in their head, notes and messages because their calendar doesn't fit them. Represents users who don't yet rely on a calendar for their life outside work, whose needs around setup and getting started differ from the system builder.

**Context.** Uses a calendar for the things with a fixed time, such as a GP appointment or a train, but leaves out anything softer. The parcel arriving "sometime tomorrow morning", the gym "after work", and the plan to "do the shopping at some point on Saturday" live in their head or scattered across notes and messages. Has never built a category system and wouldn't know where to start.

**Goal.** Get the whole of their week into one place, including the flexible things, so they can see how much room they really have and stop holding it all in their head.

**Constraints.** Won't spend time designing categories or colour schemes up front, so the product has to be useful from the first item. Has given up on putting soft plans in a calendar before, because an exact 14:00 to 15:00 entry felt untrue and an all-day entry said too little. Needs adding an item to be as quick as jotting a note, or they'll go back to jotting notes.

**Biggest frustration.** A calendar only offers two shapes, an exact slot or all day. Their plans fit neither, so they leave them out, and the calendar shows a far emptier week than the one they actually have.

**Product needs.** A quick way to add an item with a rough time or a window, so flexible plans have somewhere honest to go. Sensible starting categories they can use straight away and adjust later. The same at-a-glance view the system builder needs.

## Jobs-to-be-Done

The specific moments users reach for Calendar Cockpit.

- **Adding a fixed appointment.** When I have something at an exact time, such as a dentist appointment or a train, I want to add it with its start and end, so I know exactly when I need to be there.

- **Adding a window.** When something will happen at some point within a span of time, such as a delivery between 8 and 12, I want to add it as a window, so my calendar shows I'm committed to that span without pretending it's a fixed slot.

- **Protecting time.** When I want to set aside time for something, such as focused study or a side project, I want to block it out, so the time is held for that purpose and doesn't look free.

- **Adding a rough plan.** When I have a plan without a firm time, such as coffee at roughly 2, I want to add it as an approximate time, so it's in my calendar without looking more fixed than it is.

- **Categorising an item.** When I add something to my calendar, I want to say which part of life it belongs to, down to a sub-category such as physio within health, so I can tell what it is from its colour and label without remembering a code.

- **Marking what kind of commitment it is.** When something is pre-paid, can't be cancelled, is important, is tentative or is only an idea, I want to mark it as such, so I know what can be moved or dropped if plans change.

- **Seeing my day at a glance.** When I look at my calendar, I want to see what each piece of time is, which part of life it belongs to, and how firm it is, so I know how much room I really have.

- **Rearranging when plans change.** When something comes up, I want to see quickly which items can move and which must stay put, so I can reshuffle my day without dropping something I can't change.

- **Setting up my categories.** When I start using the app, I want to create the categories and sub-categories that match my life, or start from a sensible set and adjust it, so the calendar reflects how I think about my time from the first item.
