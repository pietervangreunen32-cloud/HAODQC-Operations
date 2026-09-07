"use server";

import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireBusiness } from "@/lib/current-business";

async function assertOwnsCombo(comboId: string, businessId: string) {
  const combo = await prisma.combo.findFirst({ where: { id: comboId, businessId } });
  if (!combo) throw new Error("Combo not found.");
  return combo;
}

function revalidateCombos() {
  revalidatePath("/admin/combos");
}

export async function createCombo(formData: FormData) {
  const { business } = await requireBusiness();

  const name = String(formData.get("name") ?? "").trim();
  const description = String(formData.get("description") ?? "").trim();
  const price = Number(formData.get("price"));

  if (!name) throw new Error("Combo name is required.");
  if (!Number.isFinite(price) || price < 0) throw new Error("Enter a valid price.");

  const last = await prisma.combo.findFirst({
    where: { businessId: business.id },
    orderBy: { order: "desc" },
  });

  await prisma.combo.create({
    data: {
      businessId: business.id,
      name,
      description: description || null,
      price,
      order: (last?.order ?? -1) + 1,
    },
  });
  revalidateCombos();
}

export async function updateCombo(formData: FormData) {
  const { business } = await requireBusiness();
  const comboId = String(formData.get("comboId") ?? "");
  await assertOwnsCombo(comboId, business.id);

  const name = String(formData.get("name") ?? "").trim();
  const description = String(formData.get("description") ?? "").trim();
  const price = Number(formData.get("price"));

  if (!name) throw new Error("Combo name is required.");
  if (!Number.isFinite(price) || price < 0) throw new Error("Enter a valid price.");

  await prisma.combo.update({
    where: { id: comboId },
    data: { name, description: description || null, price },
  });
  revalidateCombos();
}

export async function deleteCombo(comboId: string) {
  const { business } = await requireBusiness();
  await assertOwnsCombo(comboId, business.id);

  await prisma.combo.delete({ where: { id: comboId } });
  revalidateCombos();
}

export async function toggleComboActive(comboId: string, active: boolean) {
  const { business } = await requireBusiness();
  await assertOwnsCombo(comboId, business.id);

  await prisma.combo.update({ where: { id: comboId }, data: { active } });
  revalidateCombos();
}

export async function reorderCombos(orderedIds: string[]) {
  const { business } = await requireBusiness();
  const owned = await prisma.combo.findMany({
    where: { businessId: business.id },
    select: { id: true },
  });
  const ownedIds = new Set(owned.map((c) => c.id));
  if (!orderedIds.every((id) => ownedIds.has(id))) {
    throw new Error("Invalid combo list.");
  }

  await prisma.$transaction(
    orderedIds.map((id, index) =>
      prisma.combo.update({ where: { id }, data: { order: index } })
    )
  );
  revalidateCombos();
}

export async function updateShowCombosOnDisplay(show: boolean) {
  const { business } = await requireBusiness();
  await prisma.business.update({
    where: { id: business.id },
    data: { showCombosOnDisplay: show },
  });
  revalidateCombos();
}
