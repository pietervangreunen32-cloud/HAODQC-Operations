export const PLANS = ["SAMPLER", "RUSH", "FLEET"] as const;
export type PlanName = (typeof PLANS)[number];

export const PLAN_META: Record<
  PlanName,
  {
    label: string;
    tagline: string;
    priceZarPerMonth: number | null;
    productLimit: number | null; // null = unlimited
    features: string[];
  }
> = {
  SAMPLER: {
    label: "Sampler",
    tagline: "Try it out, free.",
    priceZarPerMonth: 0,
    productLimit: 10,
    features: ["Up to 10 menu items", "4 built-in themes", "Live TV display", "QR code & display link"],
  },
  RUSH: {
    label: "Rush",
    tagline: "For a truck that's actually busy.",
    priceZarPerMonth: 199,
    productLimit: null,
    features: [
      "Unlimited menu items",
      "Combos & upsells",
      "Bulk CSV import",
      "4 built-in themes",
      "Everything in Sampler",
    ],
  },
  FLEET: {
    label: "Fleet",
    tagline: "Everything, for a growing operation.",
    priceZarPerMonth: 449,
    productLimit: null,
    features: [
      "Custom brand colors & fonts",
      "Priority support",
      "Everything in Rush",
    ],
  },
};

export function planLimit(plan: PlanName): number | null {
  return PLAN_META[plan].productLimit;
}

export function planAtLeast(plan: PlanName, min: PlanName): boolean {
  return PLANS.indexOf(plan) >= PLANS.indexOf(min);
}
