# ADR Guidelines

This repository uses a small ADR format.

ADRs record architectural constraints, ownership boundaries, and invariants that future changes must
respect. They are governance documents for the codebase, not implementation documents. Accepted ADRs
define the constraints future code, tests, and follow-up design documents must respect until a later
ADR supersedes them.

## Index

The ADR index should distinguish between active and superseded ADRs. An ADR's status is authoritative
inside the ADR itself. The index is provided as a navigation aid.

Example:

### Active ADRs

* ADR-001: Primary Data Ownership Boundary
* ADR-002: Runtime Configuration Source
* ADR-003: Authentication Boundary

### Superseded ADRs

* ADR-004: Initial Module Boundary (Superseded by ADR-007)
* ADR-005: Legacy Integration Contract (Superseded by ADR-008)

## File Names

Use this format:

```text
ADR-XXX - Human readable title.md
```

Keep ADR filenames immutable once created. ADR numbers are the stable identity of the record, and
status belongs to the document content rather than the filename.

## When To Write An ADR

Write an ADR when a decision changes or fixes a durable boundary, such as tenancy, identity,
authorization, routing, data ownership, deployment shape, or frontend architecture.

Do not write an ADR for ordinary feature behavior, implementation sequencing, temporary tasks, or
details that can safely live in code, tests, a ticket, or a local design note. If the decision is not
yet made, write a design note, proposal, or issue instead.

Before creating an ADR, ask:

* Does this decision create or protect a durable boundary?
* Would future contributors benefit from knowing why this decision exists?
* Would changing this decision require deliberate discussion?
* Would violating this decision create architectural inconsistency?
* Can the decision be expressed without describing implementation steps?

If most answers are "no", the document probably should not be an ADR.

## What An ADR Is Not

An ADR is not:

* A feature specification.
* An implementation plan.
* A backlog item.
* A task list.
* A proof-of-concept design.
* A place to store open questions.

If a document mainly describes how something will be implemented, it is probably a design note. If it
mainly describes work that still needs to be done, it is probably a task or project plan.

## General Rules

* ADRs committed to the repository with status `Accepted` are considered governing records.
* Prefer short ADRs; allow longer records when the decision needs explicit boundaries or invariants.
* Prefer one decision per ADR. Closely related decisions may be grouped only when they define a
  single architectural boundary.
* Focus on project-specific reasoning.
* Lead with the decision. A reader should understand what was decided before reading why it was
  decided.
* The `Decision` section should state the adopted rule, boundary, or invariant. It should not start
  with background, motivation, or alternatives.
* State the boundary or invariant the application must preserve.
* Make ownership explicit: say which component, subsystem, or boundary owns the decision and which
  parts must obey it.
* Mention alternatives only when they clarify the decision.
* Avoid repeating the same trade-off in multiple sections.
* Accepted ADRs are historical records and should remain stable.

## Ownership And Enforcement

Every ADR should identify the component, subsystem, or architectural boundary that owns the decision.
If ownership is shared, the ADR should name the primary owning boundary and the dependent
boundaries.

The owning component defines the boundary. Other components consume, integrate with, or depend on
that boundary and must not redefine it.

When an ADR defines invariants, code reviews and future design work should treat those invariants as
requirements rather than suggestions.

## Changing Decisions

Accepted ADRs are historical records and should remain stable. Do not rewrite history by editing the
meaning of an accepted ADR.

When a decision changes:

* Create a new ADR.
* Mark the old ADR as `Superseded`.
* Reference the replacing ADR from the old ADR.
* Reference the superseded ADR from the new ADR.
* Record the new decision in the new ADR.

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
* Give implementers and reviewers enough guidance to identify and reject incompatible changes.
* Say what is intentionally out of scope when that prevents future ambiguity.

A useful ADR can often be summarized by reading only its title, status, decision, and consequences.

Avoid:

* Marketing language.
* Generic architecture essays.
* Restating external documentation.
* Describing implementation details that belong elsewhere.
* Listing options just to fill a template.
* Using ADRs as feature specifications, backlogs, or implementation checklists.
* Mixing accepted decisions with unresolved design questions.
