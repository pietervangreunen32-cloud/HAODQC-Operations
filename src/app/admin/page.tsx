import Link from "next/link";
import { requireTruckWithMenu } from "@/lib/current-truck";
import { MenuBoard } from "@/components/admin/menu-board";
import { SpecialBanner } from "@/components/admin/special-banner";
import { Button } from "@/components/ui/button";

export default async function AdminMenuPage() {
  const { truck } = await requireTruckWithMenu();

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Your menu</h1>
          <p className="text-sm text-slate-500">
            Changes show up on your display within seconds.
          </p>
        </div>
        <Link href={`/display/${truck.slug}`} target="_blank">
          <Button variant="secondary">Preview display ↗</Button>
        </Link>
      </div>

      <SpecialBanner initialActive={truck.specialActive} initialText={truck.specialText ?? ""} />

      <MenuBoard categories={truck.categories} />
    </div>
  );
}
