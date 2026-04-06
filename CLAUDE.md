# claude.md
## Project: Lean Business Operations Platform (CRM + Ticketing + Billing + Reporting + Monitoring)

You are Claude AI working as an AI Product Developer / Product Owner / Software Designer for this project.
Your job is to help design and implement a lean, custom-built internal business management platform.

This is NOT a generic CRM. Avoid suggesting large/bloated open-source CRM frameworks.
Focus on a modular, purpose-built system that matches the product requirements below.

---

# 1) Product Vision

Build a streamlined internal platform that combines:

- Client intake & management (CRM-lite)
- Ticketing (including Email → Ticket)
- Billing & invoicing
- Value-based reporting (AI summaries + GitHub activity)
- AI-driven scope generation via chat
- Revenue & receivables dashboard
- Subscription bill tracking (API where possible, manual fallback)
- Uptime monitoring for hosted web apps
- Branded PDF exports and email sending
- Appearance/UI is extremely practical, Very Modern and professional

Primary goal:
**Communicate value to clients, reduce operational risk, and track revenue without per-user SaaS fees.**

---

# 2) Core Modules (Must-Haves)

## A. Client Management
Store and manage clients with:
- Company name
- Contacts (name, email, phone)
- Billing preferences / terms
- Client notes
- Links to: tickets, reports, invoices, scopes

### Client Pricing Presets
Each client can have a preset bundled fee (e.g., quarterly maintenance + hosting bundle):
- Stored per client
- Can auto-populate invoices
- Editable per invoice before sending

---

## B. Ticketing System
### Ticket Creation Methods
1) Client portal form
2) Email-to-ticket conversion from a dedicated mailbox

### Email-to-Ticket Requirements
- Poll mailbox or webhook ingestion
- Sender email maps to a client
- Create ticket with:
  - Subject
  - Body
  - Attachments
  - Timestamp
- Store original email metadata for traceability

### Ticket Fields
- Ticket ID
- Client
- Status: Open / In Progress / Waiting / Closed
- Priority (optional)
- Assigned user (internal)
- Activity log
- Internal notes
- Attachments
- Links to invoice and/or scope

---

## C. Billing & Invoice Management
Support:
- One-off invoices (manual line items)
- Report-linked invoices
- Scope-linked invoices
- Preset bundle fee invoices per client

### Invoice Workflow
- Draft → Sent → Paid / Overdue / Cancelled
- Manual payment marking
- Outstanding receivables tracking

### Scope-Linked Invoice Requirement
If an invoice includes a line item tied to a scope:
- Scope PDF must be attached to the email
- Invoice PDF must include a note referencing the accompanying scope

Example invoice note:
“Please see accompanying scope document for the detailed breakdown of this line item.”

---

## D. Reporting Engine (GitHub + AI Value Summary)
Purpose:
Convert development activity into client-friendly reports that communicate value.

### GitHub Integration Requirements
Use GitHub API to pull:
- Commits within a date range
- Branches included (optional filter)
- PRs / merges if useful

### AI Requirements
Use Claude to generate client-friendly summaries from commit history (and optionally PR titles):
Organize output into:
- Features delivered
- Bugs fixed
- Improvements/optimization
- Security/stability
- Infrastructure/maintenance

Avoid overly technical phrasing where possible.
Always translate into business value.

---

## E. AI Scope Builder (Chat-Style)
A chat-like interface where:
- User provides initial project description
- Claude asks clarifying questions
- Claude outputs a structured scope

Scope structure:
- Executive summary
- Objectives
- Deliverables
- Timeline (high-level)
- Assumptions
- Exclusions / Out-of-scope
- Optional: Pricing placeholder

Must support linking scope to invoice line items.

---

## F. Branded PDF Generation
Reports and invoices must export to PDF with company branding.

### Branding Settings
Include a settings area that stores:
- Company logo upload
- Company name
- Phone
- Email
- Website
- Address
- Footer text

PDF outputs must use these values in headers/footers.

---

## G. Email Sending + Preview
System must support:
- Preview report PDF before sending
- Preview invoice PDF before sending
- Send to client (email)
- Attach:
  - Report PDF
  - Invoice PDF (optional)
  - Scope PDF (if linked)

---

## H. Dashboards (Revenue + Receivables)
Dashboard must show:
- Revenue for a selected period
- Outstanding payments / overdue
- Paid invoices
- Filters:
  - date range
  - client
  - status
  - invoice type

---

## I. Subscription Bill Tracking (Operational Continuity)
Target services:
- DigitalOcean
- SendGrid
- Laravel Forge
- Google Workspace

Requirements:
1) If API supports it: check renewal/billing status automatically.
2) If API not possible: allow manual tracking:
   - due date
   - amount
   - paid/unpaid
   - monthly recurrence behavior

Dashboard must warn:
- due soon
- overdue

---

## J. Uptime Monitoring (Web Apps)
Dashboard must show:
- list of platforms/web apps
- status: Up / Degraded / Down
- last check time
- response time

Method:
- scheduled HTTP ping/check with thresholds

---

# 3) AI Provider Requirement

Use Claude as the AI provider (Anthropic).
Assume we will generate and store an API key.

Claude is used for:
- GitHub-based report summaries
- Scope builder conversation + scope generation
- Optional: enhancing invoice descriptions

---

# 4) Implementation Principles

- Keep it lean and modular.
- Prefer Laravel (existing ecosystem) unless a simpler solution is clearly better.
- Design for:
  - clear database schema
  - auditability (logs)
  - exportable PDFs
  - easy operational use
- Always provide:
  - DB schema suggestions
  - route/controller outlines
  - job/queue design for email/GitHub polling
  - clear acceptance criteria

---

# 5) Output Style Requirements (How Claude Should Respond)

When asked to design or implement:
- Provide structured deliverables (headings, bullet lists)
- Provide database tables and relationships
- Provide API routes (REST)
- Provide UI screen list (wireframes in text)
- Provide acceptance criteria per feature
- Provide edge cases + failure modes
- Prefer practical, implementation-ready detail

Avoid fluff.
Avoid “big CRM” solutions unless explicitly requested.

---

# 6) MVP Roadmap (Suggested)

Phase 1 (MVP-1):
- Client management
- Invoice creation + PDF export
- Branding settings
- Revenue dashboard (basic)

Phase 2 (MVP-2):
- Ticketing + email ingestion
- Report generation framework (without GitHub AI, if needed)

Phase 3 (MVP-3):
- GitHub integration + Claude summaries
- Scope builder + scope linking

Phase 4:
- Subscription bill tracking + alerts
- Uptime monitoring dashboard

---

# 7) Security & Access Control

- Internal staff user roles (admin, staff)
- Clients have portal access limited to:
  - creating tickets
  - viewing their own tickets
  - viewing invoices and reports sent to them (optional for MVP)
- Ensure data isolation by client.

---

# 8) Non-Goals (For Now)

- Full Salesforce replacement
- Marketing automation
- Complex sales pipeline features
- Heavy customization engines

---

# 9) Definition of Done (Global)

A feature is “done” when:
- It is usable end-to-end in UI
- DB schema + migrations exist
- Basic tests exist (where practical)
- PDF output looks professional and branded
- Emails send correctly with correct attachments
- Dashboard updates reflect new data
- Errors are logged and visible to admins

---
