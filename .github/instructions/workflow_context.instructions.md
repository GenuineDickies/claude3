---
description: Require actor-channel-stage workflow analysis before implementing consent, compliance, or other workflow-sensitive features.
globs: "**/*"
alwaysApply: true
---

- **Apply A Workflow Context Gate Before Sensitive Changes**
  - Before implementing consent, compliance, notification, approval, intake, onboarding, or payment workflow changes, identify the workflow context first.
  - Resolve these questions explicitly:
    - actor: who is taking the action
    - channel: phone, internal app, public website, SMS, email, or another medium
    - stage: intake, onboarding, dispatch, fulfillment, follow-up, billing, or another workflow step
    - permission owner: who is authorized to grant or record the decision
  - If any of these are unclear, stop and verify before writing code.

- **Use This Gate In The Highest-Risk Domains**
  - Apply heightened scrutiny in these areas because the software operator is often not the real-world decision owner:
    - consent and communications
    - customer intake and dispatch handoff
    - technician onboarding and profile completion
    - estimates, approvals, signatures, and change orders
    - invoices, payments, refunds, and billing acknowledgements
    - documents, evidence capture, and audit trails
    - admin editing of another person's record

- **Use The Active Channel As A Design Constraint**
  - The correct implementation depends on how the person is interacting with the business at that moment, not just on what a generic provider workflow supports.
  - Example:
    ```text
    Customer on phone with dispatcher during intake
    -> consent method should be verbal consent recorded by staff
    -> do not replace that with a website self-consent flow
    ```

- **Do Not Generalize From Provider Defaults**
  - Provider features such as opt-in pages, keyword flows, or consent templates are not automatically correct for this application.
  - The repo's business workflow rules override generic vendor patterns unless the user explicitly changes the workflow.

- **Current Consent Matrix**
  - Customer + phone intake + service-request creation:
    - consent owner: customer
    - recording mechanism: staff records verbal consent
    - forbidden alternative: customer website self-consent for this workflow
  - Technician + signed-in account + onboarding/profile completion:
    - consent owner: technician
    - recording mechanism: technician self-service consent in their own account
    - forbidden alternative: admin granting technician consent on their behalf

- **Apply The Same Reasoning Beyond Consent**
  - Estimate approval:
    - determine whether approval is happening during a live phone call, via a public approval link, or in person
    - do not replace a live staff-mediated approval workflow with a self-service pattern unless the business flow actually changed
  - Payments:
    - distinguish between the person entering payment in the app and the person authorizing the charge or acknowledging payment
    - choose the confirmation and evidence flow based on that distinction
  - Evidence capture:
    - determine whether the uploaded photo, signature, or document is first-party customer evidence, technician evidence, or staff-entered evidence
    - store audit metadata that matches the actual source and stage
  - Admin actions:
    - when an admin edits another user's account, separate "who is operating the screen" from "who owns the decision"
    - do not let admin convenience silently replace self-service requirements

- **Escalation Rule**
  - If a proposed change would alter who grants consent, where consent is granted, or the sequence between consent and messaging, restate the workflow and get confirmation before implementation.
  - Also escalate when a change would alter:
    - who approves work or pricing
    - who authorizes payment
    - who attests to evidence or signatures
    - who acknowledges compliance-related disclosures