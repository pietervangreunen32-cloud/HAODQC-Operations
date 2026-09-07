// WooCommerce billing integration.
//
// MenuScreen itself never touches card details — Rush/Fleet are sold as
// WooCommerce products on a separate WordPress site (WOOCOMMERCE_SITE_URL).
// A "checkout URL" here just deep-links into that store's add-to-cart flow.
// After a successful purchase, WooCommerce calls our webhook endpoint
// (see src/app/api/webhooks/woocommerce/route.ts), which is what actually
// updates Business.plan — these helpers only build the outbound link and
// map WooCommerce product IDs back to a plan.

import { PlanName } from "@/lib/plans";

type PaidPlan = Extract<PlanName, "RUSH" | "FLEET">;

function productIdFor(plan: PaidPlan): string | undefined {
  if (plan === "RUSH") return process.env.WOOCOMMERCE_RUSH_PRODUCT_ID;
  if (plan === "FLEET") return process.env.WOOCOMMERCE_FLEET_PRODUCT_ID;
}

// null means billing isn't configured yet (env vars unset) — callers should
// show a "not available yet" state instead of a dead/broken link.
export function checkoutUrl(plan: PaidPlan): string | null {
  const site = process.env.WOOCOMMERCE_SITE_URL;
  const productId = productIdFor(plan);
  if (!site || !productId) return null;
  return `${site.replace(/\/+$/, "")}/?add-to-cart=${encodeURIComponent(productId)}&quantity=1`;
}

export function planForProductId(productId: string | number | undefined): PlanName | null {
  if (productId === undefined || productId === null) return null;
  const id = String(productId);
  if (id === process.env.WOOCOMMERCE_FLEET_PRODUCT_ID) return "FLEET";
  if (id === process.env.WOOCOMMERCE_RUSH_PRODUCT_ID) return "RUSH";
  return null;
}

// A WooCommerce order/subscription can carry multiple line items; if more
// than one plan product is somehow in there, the highest tier wins.
export function planFromLineItems(
  lineItems: Array<{ product_id?: number | string }> | undefined
): PlanName | null {
  if (!lineItems || lineItems.length === 0) return null;
  let found: PlanName | null = null;
  for (const item of lineItems) {
    const plan = planForProductId(item.product_id);
    if (plan === "FLEET") return "FLEET";
    if (plan === "RUSH") found = "RUSH";
  }
  return found;
}
