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

* ADR-101: Primary Data Ownership Boundary
* ADR-102: Runtime Configuration Source
* ADR-103: Authentication Boundary

### Superseded ADRs

* ADR-104: Initial Module Boundary (Superseded by ADR-107)
* ADR-105: Legacy Integration Contract (Superseded by ADR-108)

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
* Would violating this decision break an existing invariant or introduce architectural inconsistency?
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
* ADRs must use [template.md](template.md) unless a deliberate exception is explained in the ADR.
* Prefer short ADRs; allow longer records when the decision needs explicit boundaries or invariants.
* Prefer one architectural boundary per ADR. Tightly coupled invariants may be grouped when they
  protect the same boundary.
* Focus on project-specific reasoning.
* An ADR should remain understandable years later without requiring access to tickets, chats, meeting
  notes, or external systems.
* Lead with the decision. A reader should understand what was decided before reading why it was
  decided.
* The `Decision` section should state the adopted rule, boundary, or invariant. It should not start
  with background, motivation, or alternatives.
* State the boundary or invariant the application must preserve.
* Make ownership explicit: say which component, subsystem, or boundary owns the decision and which
  parts must obey it.
* When practical, describe how important invariants are enforced or reviewed.
* Mention alternatives only when they clarify the decision.
* Avoid repeating the same trade-off in multiple sections.
* Accepted ADRs are historical records and should remain stable.

## Ownership And Enforcement

Every ADR should identify the component, subsystem, or architectural boundary that owns the decision.
If ownership is shared, the ADR should name the primary owning boundary and the dependent
boundaries.

The owning component defines the boundary. Other components consume, integrate with, or depend on
that boundary and must not redefine it.

When an ADR defines invariants, those invariants are binding requirements. Violations should be
blocked through tests, static analysis, code review, or follow-up design work as appropriate.

## Changing Decisions

Accepted ADRs are historical records and should remain stable. Do not rewrite history by changing the
meaning of an accepted ADR.

Minor clarifications, consequence updates, validation improvements, and reference updates may be
added to an accepted ADR when they preserve the original decision. Supersession is required when the
boundary, invariant, or ownership rule fundamentally changes.

When a decision changes:

* Create a new ADR.
* Mark the old ADR as `Superseded`.
* Reference the replacing ADR from the old ADR.
* Reference the superseded ADR from the new ADR.
* Record the new decision in the new ADR.

## Status Values

* `Accepted`: the decision is current.
* `Superseded`: the decision was replaced by a later ADR.
* `Rejected`: the option or proposal was considered but not adopted. Ordinary alternatives should
  usually be recorded in `Why`, not as separate ADRs.

## Writing Guidance

Good ADRs:

* Define a durable decision and its consequences.
* Explain why a decision was made.
* Capture trade-offs.
* Link related ADRs, dependent boundaries, and downstream design notes when they materially help
  trace architectural evolution.
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
