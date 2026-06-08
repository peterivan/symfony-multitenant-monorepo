# ADR-002: Back Office

## Status
Accepted  
Date: 2026-05-15

## Context
Proxia.Plan requires an internal operational interface for platform operators.

Platform operations have different responsibilities, permissions, data scope, and interaction patterns
than tenant-facing workflows. Mixing these concerns would blur ownership boundaries, make
authorization harder to reason about, and increase the risk of exposing operational capabilities in
tenant-facing areas.

The application therefore needs an explicit Back Office boundary.

## Decision
The platform will provide a dedicated Back Office application section under `/bo`.

Back Office is an internal operational application for platform-level operations only. It is not part
of the tenant workspace and must not host tenant business workflows.

Back Office is responsible for operational capabilities that configure, inspect, support, or maintain
the platform as a whole. Tenant-facing business workflows belong outside Back Office, even when
performed by privileged tenant users.

Back Office must remain separated from tenant-facing areas by:
- route namespace and URL boundary
- layout and navigation structure
- authorization model
- operator-focused UX conventions
- data access rules

Back Office routes must be registered only for central application contexts. They must not be
reachable as tenant-subdomain routes.

Back Office permissions are granted to platform operators, not tenant users. Access decisions must
remain independent from tenant-local roles and workspace permissions.

Back Office UI should prioritize operational efficiency, information density, fast navigation, and
clear system state over tenant-facing product ergonomics.

Any Back Office capability that reads, derives, exports, mutates, or deletes tenant-owned data must
make the tenant boundary explicit and must avoid silently acting inside an ambiguous tenant context.

## Consequences

### Positive
- clear separation between platform operations and tenant workflows
- simpler authorization reasoning for privileged functionality
- reduced risk of operational tools leaking into tenant-facing areas
- independent evolution of operator UX and tenant UX
- clearer ownership boundaries for platform administration code

### Negative
- additional route, layout, navigation, and authorization structure to maintain
- some shared UI or domain concepts may need duplication or adapters
- cross-tenant operations require explicit handling and stronger safeguards

### Operational rules
- Back Office routes live under `/bo`
- Back Office routes are central-context only
- tenant business workflows must not be implemented in Back Office
- tenant-local permissions must not grant Back Office access
- Back Office actions must use platform-operator authorization
- tenant context must be explicit whenever Back Office inspects or affects tenant-owned data
- privileged Back Office actions must produce audit records
- Back Office navigation and layout evolve independently from tenant-facing navigation
- only operational platform capabilities belong behind this boundary
