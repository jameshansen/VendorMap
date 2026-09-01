# TODO

Planned work that is **not** implemented yet. Deliberately deferred — see each entry for why.

> **Superseded in part.** The account-versus-booking split below is now addressed differently by the
> three-service design in [ARCHITECTURE-PROPOSAL.md](ARCHITECTURE-PROPOSAL.md), where per-event boxes make
> approval per-event by construction. The problem statement here still holds and is worth reading first; the
> proposed solution is the one in the architecture proposal.

## Separate vendor screening from booking screening

**Status:** deferred until after the 2026 Christmas Market (too large a change to land with an event imminent).

### The problem

Today there is only one gate: a vendor **account** is approved or rejected, and that decision is effectively being made on the basis of one specific event. That conflates two different questions:

1. *Is this a legitimate vendor we are willing to deal with at all?* (a property of the **account**, long-lived)
2. *Do we want this vendor at **this** event?* (a property of a **booking**, per event)

Because only the first gate exists, a vendor turned away for event-specific reasons — the category is already full, the mix is unbalanced, there is no room — ends up **rejected as an account**. Next year, when we would happily have them back, their account carries a rejection that had nothing to do with them being a bad vendor. The rejection also emails them as though they were refused outright, which is the wrong message.

### The intended shape

- **Account screening stays**, but narrows to its real question: is this a real, legitimate business we will deal with? Approve once, and it persists across years.
- **Booking screening is new**: when an approved vendor books a table for an event, that booking itself is reviewable — approve, decline, or waitlist for *that event only*, without touching the account.
- Declining a booking must be clearly distinct from rejecting an account, in the admin UI and in the vendor-facing email ("not this time" vs "not at all").
- The existing `event_tables.status` (`available` / `held` / `booked`) already models a per-event hold, so booking-level approval is likely an extension of that flow (e.g. a `declined` / `waitlisted` status plus a reason) rather than a new table.
- Historical view worth having: per vendor, which events they have attended, so returning vendors are recognisable at a glance.

### Knock-on: AI Vendor Guidance moves to the event

The `ai_guidance` block currently lives in `config.php` as a single site-wide setting, including the `goal` that describes the desired vendor mix. That is wrong for the same reason: **the desired mix is a property of an event, not of the site.** A Christmas market and a spring baby-goods fair want different mixes, and the same vendor may fit one and not the other.

When this work happens:

- Move the guidance spec (at minimum `goal`, possibly `enabled` and `model`) onto the **event** record, editable in the event form, with the `config.php` values acting as the default for new events.
- Point the guidance at a specific event's bookings rather than at the global approved-vendor list, so the summary answers "what does the mix for *this event* look like, and what would approving these bookings do to it?"
- Keep the credentials (`api_key`, `url`) in `config.php` — those are deployment settings, not per-event ones.

### Migration notes

- Existing accounts rejected for event-specific reasons should be reviewable: consider a one-off pass to restore them to approved once booking-level screening exists.
- The vendors admin page already shows booking status per event (added ahead of this work), which is the read-only half of the eventual per-event view.
