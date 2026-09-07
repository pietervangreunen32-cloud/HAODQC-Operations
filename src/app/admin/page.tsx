import Link from "next/link";
import { requireBusinessWithMenu } from "@/lib/current-business";
import { MenuBoard } from "@/components/admin/menu-board";
import { SpecialBanner } from "@/components/admin/special-banner";
import { CsvImportForm } from "@/components/admin/csv-import-form";
import { Button } from "@/components/ui/button";
import { planLimit } from "@/lib/plans";

export default async function AdminMenuPage() {
  const { business } = await requireBusinessWithMenu();

  const itemCount = business.categories.reduce((sum, cat) => sum + cat.items.length, 0);
  const limit = planLimit(business.plan);
  const atLimit = limit !== null && itemCount >= limit;

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Your menu</h1>
          <p className="text-sm text-slate-500">
            Changes show up on your display within seconds.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <CsvImportForm plan={business.plan} />
          <Link href={`/display/${business.slug}`} target="_blank">
            <Button variant="secondary">Preview display ↗</Button>
          </Link>
        </div>
      </div>

      {atLimit && (
        <div className="rounded-lg bg-orange-50 px-4 py-3 text-sm text-orange-800">
          You&apos;ve used all {limit} items on the Sampler plan. Adding a new item will fail until
          you{" "}
          <Link href="/admin/upgrade" className="font-medium underline">
            upgrade to Rush
          </Link>{" "}
          for unlimited items.
        </div>
      )}

      <SpecialBanner initialActive={business.specialActive} initialText={business.specialText ?? ""} />

      <MenuBoard categories={business.categories} />
    </div>
  );
}
