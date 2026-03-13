# Workflow Context Guide

Last audited: 2026-03-13

This guide explains when advanced workflow reasoning is required and how to apply it before changing sensitive features.

## 1. Core Rule

Do not choose a workflow from generic product patterns alone.

Before implementing a workflow-sensitive change, identify:

1. Actor: who is taking the action.
2. Channel: where the interaction is happening.
3. Stage: when in the workflow it occurs.
4. Permission owner: who is allowed to grant, approve, attest, or acknowledge the decision.
5. Recording mechanism: how that decision should be captured in the application.

If those answers are not explicit, stop and resolve them before implementation.

## 2. When To Use This Level Of Reasoning

Apply this framework whenever a task affects any of the following:

1. Consent and customer or technician communications
2. Customer intake and dispatch handoff
3. Technician onboarding or compliance completion
4. Estimates, approvals, signatures, and change orders
5. Invoices, payments, refunds, and billing acknowledgements
6. Evidence capture, documents, and audit trails
7. Admin edits to another person's account or settings

## 3. Decision Matrix

| Area | Actor | Channel | Stage | Permission owner | Typical correct mechanism | Typical mistake |
| --- | --- | --- | --- | --- | --- | --- |
| Customer SMS consent | Customer + dispatcher | Phone + internal staff UI | Intake | Customer, recorded by staff | Verbal consent captured by staff | Website self-consent when customer is not on the site |
| Technician SMS consent | Technician | Signed-in app account | Onboarding or profile completion | Technician | Self-service consent in own account | Admin grants consent on technician's behalf |
| Estimate approval | Customer or authorized payer | Phone, in person, or public approval page | Pre-work authorization | Customer or authorized payer | Use the channel that matches the live interaction | Forcing self-service when approval is happening live with staff |
| Payment confirmation | Customer, payer, or staff | Phone, in person, payment workflow | Billing | Payer | Capture payment evidence that matches how the payment was authorized | Treating the app operator as the payer |
| Signature or evidence capture | Customer, technician, or staff | Public page, in-app form, or upload flow | Fulfillment or closeout | Person providing the evidence | Store source-specific audit metadata | Losing who actually supplied the evidence |
| Admin account edits | Admin editing another user | Internal app | Maintenance or onboarding | User for self-owned decisions; admin for administrative fields | Separate record maintenance from permission ownership | Letting admin convenience replace self-service rules |

## 4. How To Evaluate A Proposed Change

Use these questions before changing a sensitive workflow:

1. Who is physically or digitally present at the time the decision is made?
2. Is the person granting permission the same person using the screen?
3. Is this synchronous with staff interaction, or asynchronous self-service?
4. Does the proposed design ask the person to repeat information already known?
5. Would the design change who grants permission, where it is granted, or when it is granted?

If the answer to question 5 is yes, the workflow should be restated and confirmed before implementation.

## 5. Repo-Specific Defaults

These defaults apply unless explicitly changed by the user:

1. Customer SMS consent during phone intake is verbal and is recorded by staff.
2. Technician SMS consent is self-service only in the technician's own signed-in account.
3. Existing phone information should be reused where possible.
4. Internal duplication can be acceptable, but repeated user data entry is usually a workflow bug.

## 6. Practical Examples

### Example: Customer on the phone during intake

1. Actor: customer speaking with dispatcher
2. Channel: phone call plus internal dispatcher UI
3. Stage: intake
4. Permission owner: customer
5. Correct mechanism: dispatcher records verbal consent

Incorrect alternative:

1. Send the customer to a website to self-consent during the same phone-intake workflow.

### Example: Technician receiving dispatch texts

1. Actor: technician
2. Channel: signed-in technician profile page
3. Stage: onboarding or profile completion
4. Permission owner: technician
5. Correct mechanism: technician enters or confirms phone number and grants SMS consent

Incorrect alternative:

1. Admin enters a second phone number and grants consent for the technician.

### Example: Customer estimate approval

1. If the customer is already live with staff on a call, a staff-mediated approval workflow may be the right fit.
2. If the customer is not live with staff and needs time to review, a public approval page can be the right fit.

The correct answer depends on the channel and stage, not on a single universal approval pattern.