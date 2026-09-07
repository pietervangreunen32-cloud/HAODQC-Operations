import crypto from "crypto";
import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { planFromLineItems } from "@/lib/woocommerce";

// WooCommerce order/subscription statuses that mean "the paid plan should
// be (re-)applied" vs. "the plan should fall back to Sampler".
const ACTIVATING_STATUSES = new Set(["completed", "processing", "active"]);
const DEACTIVATING_STATUSES = new Set(["cancelled", "refunded", "failed", "expired", "on-hold", "pending-cancel"]);

type WooLineItem = { product_id?: number | string };
type WooWebhookPayload = {
  status?: string;
  billing?: { email?: string };
  line_items?: WooLineItem[];
};

// Configure this URL as a webhook in WooCommerce (WooCommerce → Settings →
// Advanced → Webhooks) for topics "Order updated" and, if the WooCommerce
// Subscriptions extension is installed, "Subscription updated". Set the
// webhook secret to WOOCOMMERCE_WEBHOOK_SECRET below.
export async function POST(req: Request) {
  const secret = process.env.WOOCOMMERCE_WEBHOOK_SECRET;
  if (!secret) {
    return NextResponse.json({ error: "WooCommerce webhook is not configured." }, { status: 501 });
  }

  const rawBody = await req.text();
  const signature = req.headers.get("x-wc-webhook-signature");
  if (!signature || !isValidSignature(rawBody, signature, secret)) {
    return NextResponse.json({ error: "Invalid signature." }, { status: 401 });
  }

  let payload: WooWebhookPayload;
  try {
    payload = JSON.parse(rawBody);
  } catch {
    return NextResponse.json({ error: "Invalid JSON." }, { status: 400 });
  }

  // WooCommerce sends a near-empty test delivery when a webhook is first
  // created — nothing to do with it but acknowledge it.
  const billingEmail = payload.billing?.email?.toLowerCase().trim();
  const status = payload.status;
  if (!billingEmail || !status) {
    return NextResponse.json({ ok: true });
  }

  const business = await prisma.business.findFirst({
    where: { owner: { email: billingEmail } },
  });
  if (!business) {
    // No MenuScreen account with this email — nothing to match yet.
    return NextResponse.json({ ok: true });
  }

  const plan = planFromLineItems(payload.line_items);
  if (!plan || plan === "SAMPLER") {
    return NextResponse.json({ ok: true });
  }

  if (ACTIVATING_STATUSES.has(status)) {
    if (business.plan !== plan) {
      await prisma.business.update({ where: { id: business.id }, data: { plan } });
    }
  } else if (DEACTIVATING_STATUSES.has(status)) {
    // Only downgrade if the cancelled/expired order is for the plan the
    // account is currently on — an unrelated cancelled order shouldn't
    // knock someone off a plan they still hold via another order.
    if (business.plan === plan) {
      await prisma.business.update({ where: { id: business.id }, data: { plan: "SAMPLER" } });
    }
  }

  return NextResponse.json({ ok: true });
}

function isValidSignature(rawBody: string, signatureHeader: string, secret: string): boolean {
  const expected = crypto.createHmac("sha256", secret).update(rawBody, "utf8").digest("base64");
  const expectedBuf = Buffer.from(expected);
  const givenBuf = Buffer.from(signatureHeader);
  if (expectedBuf.length !== givenBuf.length) return false;
  return crypto.timingSafeEqual(expectedBuf, givenBuf);
}
