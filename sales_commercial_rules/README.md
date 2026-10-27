# Sales Commercial Rules 0.1.0
Phase 1 independent replacement foundation for item_discount.

Includes:
- Independent sale types table with compatibility sync to `item_discount_sell_type`.
- Independent group discount rules with overlap validation.
- Non-destructive migration of existing sale types and discount rules on activation.
- Existing `item_sale_type_prices` compatibility, preserving Product SQM Pricing 0.1.11.
- Invoice sale type -> item sale type price frontend and server-side enforcement.
- Avoids duplicate invoice fields while old `item_discount` remains active.

Not yet included in 0.1.0: hierarchy, permissions, commission ledger/reversal, ranking.
Do not uninstall the old module yet. Test in parallel, then proceed to Phase 2.


## 0.2.0 Phase 2
- Sales manager/subordinate hierarchy with add/edit/delete.
- Non-destructive migration from item_discount_set_middle_staff.
- Per-staff permission registry for editing invoice line discount percentages.
- Schema auto-ensure on admin_init for safe upgrade from 0.1.3.

## 0.2.2
- Manager-scoped, revocable elevated discount authorization.
- Normal seller ceiling = base percent; authorized seller ceiling = max percent.
- Access page only exposes mapped subordinates (admin may inspect a manager).
- Added discount policy endpoint for invoice integration.


## 0.2.3 hotfix
Access authorization storage moved to a dedicated manager-scoped table to avoid legacy schema conflicts during upgrades.


## 0.4.0
- Fixed commission legacy re-import after deletion.
- Added independent ranking periods, ranking steps, edit/delete, legacy migration, manual calculation and result register.


## 0.4.1
- Fixed legacy commission resurrection on module activation/update.
- If an older build already resurrected a deleted legacy commission, explicitly adding that same group + sale type now adopts it as an independent SCR rule instead of raising a false duplicate error.
- Native duplicate protection remains enabled.
- Ranking functionality from 0.4.0 is unchanged.

## 0.6.0 Ranking Engine
Ranking periods now contain only title and date range. Sale type and ranked role are assigned to each ranking step. Calculation builds a payment-level invoice ledger and aggregates final ranking results by staff + role + sale type. Existing ranking periods and steps are preserved; scope fields from 0.5.2 are migrated from period to existing steps where available.


## 0.6.2 - Canceled commission / reversal engine
- Canceled invoices (Perfex status 5) are excluded from an unpaid ranking-period recalculation.
- Ranking periods can be explicitly marked as commission-paid; paid periods are locked for audit integrity.
- A cancellation discovered after a paid period creates seller and marketer/manager reversal rows.
- Reversal includes both the payment's base commission and its proportional share of the paid ranking adjustment.
- Pending reversals are automatically carried into the next calculated period for the same staff/role/sale type and shown as a negative prior-period reversal.
- Added Canceled Commissions register with source period, applied period, base reversal, rank-adjustment reversal and pending/applied status.
- Legacy canceled-commission records remain preserved by the 0.6.1 full migration snapshot.

## 0.7.0 Daily Cashier Handover
Captures newly recorded invoice payments against the recording staff member and business date, summarizes Cash/POS/Bank/Other, supports actual cash declaration, printable handover report, and accountant/admin approval or discrepancy status. Payment-mode category mapping is configurable from the cashier page.


## 0.7.1 Sales Menu Placement
- Moved Daily Cashier Handover out of Commercial Rules submenu.
- Cashier entry is now registered under Perfex native Sales menu.
- No cashier workflow, pricing, product picker, commission, or ranking logic changed.

## 0.7.2 Cashier permissions and discrepancy workflow
Adds separate cashier/accountant/settings permissions, locks cashier amounts after submission, discrepancy return/response/resubmit, approve-with-discrepancy closing, and an audit trail.


## 0.7.3
Cashier UI split into permission-aware tabs; discrepancy labels are Persian/user-facing; payment update/delete hooks synchronize cashier links. Open sessions recalculate from live payments, while post-handover payment changes create a system discrepancy and reopen the report for accounting review.


## 0.7.4.1
- Stabilized invoice product picker state; bootstrap-select is refreshed without destructive reinitialization.
- Added draft-item clear (X) action beside the native add-item control.
- Debounced product-code filtering and reduced MutationObserver churn.
- Added sale-price request cache/deduplication and stale-selection protection.
- Sale-type changes now invalidate price cache and reprice selected/existing invoice items.

## 0.7.5 Seller requests + cashier menu label
Cashier submenu shortened to «مدیریت صندوق». Adds «دریافتی از فروشندگان» under Sales, with invoice/estimate/return decisions and request hydration into standard Perfex document forms.


## 0.7.6.9.7.15
Partial-payment edit guard: keeps the visible native Perfex amount field editable while partial receipt is selected, without replacing the field or altering native payment submission.


## 0.7.6.9.7.16
Partial amount editor isolation: preserves the proven native Perfex payment submit path from 7.14/7.15, while a nameless editable twin is shown only for partial-payment typing and continuously synced into the real native amount field before submit.


## 0.7.6.9.7.17
Partial payment real-amount submit sync: the editable partial amount now continuously wins over customizers that restore the full balance, and is copied into the native Perfex amount before AJAX/click serialization as well as form submit.


## 0.7.6.9.7.18
Received-payments table now shows the raw Gregorian payment date stored by Perfex and the POS device captured for each payment. Payment save/partial-payment logic from 7.17 is unchanged.


## 0.7.6.9.7.19
POS-aware payments: configured POS payment mode is restored to the invoice payment selector when Perfex omits it; POS device selector is shown only for POS payment method; POS metadata is present before AJAX serialization; cashier totals automatically classify a payment mode named POS as POS; cashier detail and main Payments list show the captured POS device.


## 0.7.6.9.7.22
- Prevent auxiliary cashier synchronization errors after a native payment delete from returning HTTP 500. Perfex can now finish its normal post-delete redirect/refresh while the payment remains deleted.


## 0.7.6.9.7.23
Payment delete hook is now unlink-only: no schema/model/audit work executes during Perfex native delete, allowing the native redirect to complete while cashier totals remain synchronized.

## 0.7.6.9.7.24 - Native payment delete isolation
- Removed the module's `after_payment_deleted` listener entirely from the native Perfex delete request.
- Cashier reports already use an INNER JOIN to the native payment table, so a deleted payment is excluded from live cashier totals even if its auxiliary link remains orphaned.
- This isolates `/admin/payments/delete/{id}` from all Sales Commercial Rules post-delete execution and preserves Perfex's own redirect response.


## 0.7.6.9.7.26 - Safe payments-list delete route
- Re-routes delete actions only on `/admin/payments` to a controlled module endpoint.
- Deletes the native payment row and SCR cashier/POS link in one transaction, recalculates invoice status, and redirects directly to the payments list without invoking the failing native post-delete chain.
- Payment creation, partial payments, POS capture, device reporting, and cashier calculations are unchanged.
