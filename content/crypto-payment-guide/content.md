# How to pay with crypto — content source

Editorial copy for the Elementor guide page. Screenshots live in `media/`.

## Hero

**Title:** How to pay with crypto

**Intro:** Biopentra accepts crypto payments in two ways: pay with Bitcoin directly, or pay with your card through an independent third-party provider that converts your payment to crypto. This guide explains what to expect at checkout.

## Section A — Pay with Bitcoin (BTCPay)

1. At checkout, select the **Bitcoin / BTCPay** payment method (the exact label is shown at checkout).
2. Click **Place order**. You may be redirected to BTCPay or see an invoice modal, depending on store configuration.
3. Pay the invoice amount using your wallet (QR code, copy address, or wallet connect, as offered).
4. Complete payment before the invoice expires.
5. Order confirmation is sent by email. Timing depends on network confirmation. You may or may not return to the shop afterward.

## Section B — Pay with card (card-to-crypto overview)

When you select the Blockchain.com payment method — or any other method labelled **“Card to…”** — you are redirected to a third-party payment provider. The process is usually similar: register (first time) or log in (returning). First-time users **often** need identity verification (KYC). Follow the provider’s instructions.

**Important:** Do not change the cryptocurrency or wallet/blockchain address shown during checkout — these are set by the payment flow.

1. At checkout, select a **card-to-crypto** payment method (the provider name is shown at checkout).
2. Click **Place order** — you are redirected to that provider’s hosted checkout (not Biopentra).
3. The provider may request identity verification, depending on their requirements and your history with them.
4. Pay using a payment method accepted by that provider. Debit cards are often more reliable than credit cards, but acceptance varies.
5. After the provider completes payment, Biopentra receives confirmation asynchronously. You should receive an order confirmation email. You may or may not be redirected back to the shop.

## Section C — Blockchain.com walkthrough

### Provider checkout

**Step 1 — Amount:** You are presented with a screen for the order amount. Accept it and press **Preview buy**.

**Step 2 — Email:** Log in or supply your email address.

**Step 3 — Verify email:** Complete email verification as instructed.

**Step 4 — KYC form:** Fill out identity verification if required. This information is sent to the payment provider only — not to Biopentra.

**Step 5 — Government ID:** If instructed to complete KYC, you may need a government-issued ID (such as a passport).

**Step 6 — Payment method:** Select a payment option accepted by the provider. The example below shows **Google Pay** — available payment methods depend on the provider and your region.

**Step 7 — Complete purchase:** Review and complete the purchase. The wallet address and cryptocurrency type are pre-set for this order — do not modify them.

**Step 8 — Payment completed:** Once payment is completed on the provider site, you can ignore any subsequent pages there. The crypto transfer may take several minutes; timing varies.

**Step 9 — After payment:** Typically you are **not** returned to the shop automatically. If you return manually, your order may temporarily appear unpaid and your cart may still look populated while settlement completes — this can be normal.

### What you’ll see back in the store

While the payment is being registered, your order may show as awaiting payment. After a few minutes, the status should change to **Processing** once payment is confirmed.

When the backend has registered your payment, you will receive a confirmation email. You will receive a separate email when your order is shipped.

## Section C — Other providers

### Revolut (`vccp-gateway-revolut`)

The general flow in Section B applies. Screens, verification steps, accepted cards, and redirect behaviour **may differ** from Blockchain.com.

### Bitnovo (`vccp-gateway-bitnovo`)

The general flow in Section B applies. Screens, verification steps, accepted cards, and redirect behaviour **may differ** from Blockchain.com.

## Section D — Fees

Payment fees, if any, are shown on the payment method label and in your order total at checkout.

| | Bitcoin (BTCPay) | Card-to-crypto |
|---|---|---|
| Fee | Shown at checkout | Shown at checkout |

## FAQ

**Why was I sent to another website to pay?**  
Card-to-crypto payments are completed on the provider’s secure checkout. Biopentra does not process card details directly.

**Does Biopentra see my card, ID, or KYC details?**  
No. Identity verification and card payment are handled entirely by the third-party provider.

**Why does my order still look unpaid after I paid?**  
Settlement can take several minutes. Your order should update once the provider confirms payment to Biopentra.

**Why wasn’t I returned to the shop?**  
Many provider checkouts do not redirect back to the store. Wait for your confirmation email instead of placing a duplicate order.

**How long until my order is confirmed?**  
Often within a few minutes, but timing varies depending on the provider and payment method.

**Which payment method should I choose?**  
Bitcoin (BTCPay) suits customers with a crypto wallet. Card-to-crypto suits customers who prefer paying by card.

**What if payment fails or I close the browser?**  
Return to checkout and try again, or contact support with your order number if you were charged but the order did not update.
