import Link from "next/link";
import { requireBusiness } from "@/lib/current-business";
import { prisma } from "@/lib/prisma";
import { PLAN_META, planLimit } from "@/lib/plans";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

export default async function DashboardPage() {
  const { business } = await requireBusiness();

  const [categoryCount, itemCount, soldOutCount, comboCount] = await Promise.all([
    prisma.menuCategory.count({ where: { businessId: business.id } }),
    prisma.menuItem.count({ where: { category: { businessId: business.id } } }),
    prisma.menuItem.count({ where: { category: { businessId: business.id }, soldOut: true } }),
    prisma.combo.count({ where: { businessId: business.id } }),
  ]);

  const plan = PLAN_META[business.plan];
  const limit = planLimit(business.plan);

  const stats = [
    { label: "Menu items", value: itemCount },
    { label: "Sold out right now", value: soldOutCount },
    { label: "Categories", value: categoryCount },
    { label: "Combos & upsells", value: comboCount },
    { label: "Display loads", value: business.viewCount },
  ];

  const quickLinks = [
    { href: "/admin", label: "Manage your menu" },
    { href: "/admin/combos", label: "Manage combos" },
    { href: `/display/${business.slug}`, label: "Preview your display ↗", external: true },
    { href: "/admin/display", label: "Get your link & QR code" },
    { href: "/admin/help", label: "How to put it on a TV" },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Welcome back, {business.name}</h1>
        <p className="text-sm text-slate-500">A quick look at how things are set up.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
        {stats.map((stat) => (
          <Card key={stat.label} className="text-center">
            <p className="text-3xl font-black text-slate-900">{stat.value}</p>
            <p className="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">
              {stat.label}
            </p>
          </Card>
        ))}
      </div>

      <Card>
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 className="text-lg font-bold text-slate-900">
              Your plan: {plan.label}
            </h2>
            <p className="text-sm text-slate-500">{plan.tagline}</p>
          </div>
          <div className="text-right">
            <p className="text-2xl font-black text-slate-900">
              {plan.priceZarPerMonth === 0 ? "Free" : `R${plan.priceZarPerMonth}/mo`}
            </p>
            <p className="text-sm text-slate-500">
              {limit === null ? "Unlimited menu items" : `${itemCount} of ${limit} menu items used`}
            </p>
          </div>
        </div>
        {limit !== null && (
          <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
            <div
              className="h-full rounded-full bg-orange-500"
              style={{ width: `${Math.min(100, (itemCount / limit) * 100)}%` }}
            />
          </div>
        )}
        <ul className="mt-4 grid gap-1.5 text-sm text-slate-600 sm:grid-cols-2">
          {plan.features.map((feature) => (
            <li key={feature} className="flex items-center gap-2">
              <span className="text-emerald-600">✓</span> {feature}
            </li>
          ))}
        </ul>
      </Card>

      <Card>
        <h2 className="mb-3 text-lg font-bold text-slate-900">Quick actions</h2>
        <div className="flex flex-wrap gap-2">
          {quickLinks.map((link) => (
            <Link key={link.href} href={link.href} target={link.external ? "_blank" : undefined}>
              <Button variant="secondary">{link.label}</Button>
            </Link>
          ))}
        </div>
      </Card>
    </div>
  );
}
