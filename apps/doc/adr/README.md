# ADR Guidelines

This repository uses a small ADR format.

ADRs document decisions that are worth remembering later.

ADRs are governing decision records for durable application boundaries. They are not casual
architecture notes, design sketches, implementation plans, or task lists. Accepted ADRs define the
constraints future code, tests, and follow-up design documents must respect until a later ADR
supersedes them.

## Index

* [ADR-001: Tenancy Architecture](<ADR-001 - Tenancy architecture.md>)
* [ADR-002: Back Office](ADR-002-Back-Office-wip.md)
* [ADR-003: Tenant Workspace](ADR-003-Tenant-workspace-wip.md)
* [ADR-004: Frontend Architecture and UI Foundation](ADR-004-Frontend-architecture-and-UI-foundation.md)

## File Names

Use this format:

```text
ADR-XXX - Human readable title.md
```

## When To Write An ADR

Write an ADR when a decision changes or fixes a durable boundary, such as tenancy, identity,
authorization, routing, data ownership, deployment shape, or frontend architecture.

Do not write an ADR for ordinary feature behavior, implementation sequencing, temporary tasks, or
details that can safely live in code, tests, a ticket, or a local design note.

## General Rules

* ADRs are accepted by default when added to the repository.
* Prefer short ADRs; allow longer records when the decision needs explicit boundaries or invariants.
* Focus on project-specific reasoning.
* State the boundary or invariant the application must preserve.
* Make ownership explicit: say which part of the application owns the decision and which parts must
  obey it.
* Mention alternatives only when they clarify the decision.
* Avoid repeating the same trade-off in multiple sections.
* Keep accepted ADRs stable. If the decision changes, add a new ADR and mark the old one as superseded.

## Status Values

* `Accepted`: the decision is current.
* `Superseded`: the decision was replaced by a later ADR.
* `Rejected`: the option or proposal was considered but not adopted.

## Writing Guidance

Good ADRs:

* Define a durable decision and its consequences.
* Explain why a decision was made.
* Capture trade-offs.
* Help future contributors understand the reasoning.
* Give implementers and reviewers enough boundary guidance to reject incompatible changes.
* Say what is intentionally out of scope when that prevents future ambiguity.

Avoid:

* Marketing language.
* Generic architecture essays.
* Restating external documentation.
* Describing implementation details that belong elsewhere.
* Listing options just to fill a template.
* Using ADRs as feature specifications, backlogs, or implementation checklists.
* Mixing accepted decisions with unresolved design questions.
