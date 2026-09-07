import { requireBusiness } from "@/lib/current-business";
import { PLANS, PLAN_META, PlanName } from "@/lib/plans";
import { checkoutUrl } from "@/lib/woocommerce";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export default async function UpgradePage() {
  const { session, business } = await requireBusiness();
  const ownerEmail = session.user?.email ?? "";

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Plans &amp; billing</h1>
        <p className="text-sm text-slate-500">
          Upgrades are handled through checkout — no card details are stored on MenuScreen.
        </p>
      </div>

      {ownerEmail && (
        <Card className="bg-amber-50 border-amber-200">
          <p className="text-sm text-amber-900">
            When you check out, use <strong>{ownerEmail}</strong> as your email address — that&apos;s
            how we match your purchase back to this account and switch your plan on automatically,
            usually within a minute of payment.
          </p>
        </Card>
      )}

      <div className="grid gap-4 sm:grid-cols-3">
        {PLANS.map((planName) => (
          <PlanCard key={planName} planName={planName} currentPlan={business.plan} />
        ))}
      </div>

      <Card>
        <h2 className="mb-1 text-lg font-bold text-slate-900">How this works</h2>
        <ul className="list-disc space-y-1 pl-5 text-sm text-slate-600">
          <li>Clicking &quot;Upgrade&quot; takes you to secure checkout on our store — not this site.</li>
          <li>Once payment goes through, your MenuScreen account is upgraded automatically.</li>
          <li>Downgrading or cancelling is handled the same way, through your order/subscription.</li>
        </ul>
      </Card>
    </div>
  );
}

function PlanCard({ planName, currentPlan }: { planName: PlanName; currentPlan: PlanName }) {
  const plan = PLAN_META[planName];
  const isCurrent = planName === currentPlan;
  const url = planName === "SAMPLER" ? null : checkoutUrl(planName);

  return (
    <Card className={cn(isCurrent && "border-orange-500 ring-1 ring-orange-500")}>
      <div className="mb-1 flex items-center gap-2">
        <h2 className="text-lg font-bold text-slate-900">{plan.label}</h2>
        {isCurrent && (
          <span className="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">
            Current plan
          </span>
        )}
      </div>
      <p className="mb-3 text-sm text-slate-500">{plan.tagline}</p>
      <p className="mb-4 text-2xl font-black text-slate-900">
        {plan.priceZarPerMonth === 0 ? "Free" : `R${plan.priceZarPerMonth}/mo`}
      </p>
      <ul className="mb-4 space-y-1.5 text-sm text-slate-600">
        {plan.features.map((feature) => (
          <li key={feature} className="flex items-center gap-2">
            <span className="text-emerald-600">✓</span> {feature}
          </li>
        ))}
      </ul>

      {planName === "SAMPLER" ? (
        <p className="text-sm text-slate-400">{isCurrent ? "You're on this plan." : "Free tier."}</p>
      ) : isCurrent ? (
        <p className="text-sm text-slate-400">You&apos;re on this plan.</p>
      ) : url ? (
        <a href={url} target="_blank" rel="noopener noreferrer">
          <Button className="w-full">Upgrade to {plan.label}</Button>
        </a>
      ) : (
        <p className="text-sm text-slate-400">
          Billing isn&apos;t set up yet — check back soon or contact support.
        </p>
      )}
    </Card>
  );
}
