import { requireBusiness } from "@/lib/current-business";
import { prisma } from "@/lib/prisma";
import { CombosBoard } from "@/components/admin/combos-board";

export default async function CombosPage() {
  const { business } = await requireBusiness();

  const combos = await prisma.combo.findMany({
    where: { businessId: business.id },
    orderBy: { order: "asc" },
  });

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Combos &amp; Upsells</h1>
        <p className="text-sm text-slate-500">
          Give customers a reason to spend a little more.
        </p>
      </div>
      <CombosBoard
        combos={combos}
        initialShowOnDisplay={business.showCombosOnDisplay}
        plan={business.plan}
      />
    </div>
  );
}
