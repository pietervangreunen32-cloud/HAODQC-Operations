"use server";

import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireBusiness } from "@/lib/current-business";
import { saveUpload } from "@/lib/uploads";
import { planLimit } from "@/lib/plans";

async function assertOwnsCategory(categoryId: string, businessId: string) {
  const category = await prisma.menuCategory.findFirst({
    where: { id: categoryId, businessId },
  });
  if (!category) throw new Error("Category not found.");
  return category;
}

async function assertOwnsItem(itemId: string, businessId: string) {
  const item = await prisma.menuItem.findFirst({
    where: { id: itemId, category: { businessId } },
    include: { category: true },
  });
  if (!item) throw new Error("Item not found.");
  return item;
}

function revalidateAdmin() {
  revalidatePath("/admin");
}

// ---------- Categories ----------

export async function createCategory(name: string) {
  const { business } = await requireBusiness();
  const trimmed = name.trim();
  if (!trimmed) throw new Error("Category name is required.");

  const last = await prisma.menuCategory.findFirst({
    where: { businessId: business.id },
    orderBy: { order: "desc" },
  });

  await prisma.menuCategory.create({
    data: { businessId: business.id, name: trimmed, order: (last?.order ?? -1) + 1 },
  });
  revalidateAdmin();
}

export async function renameCategory(categoryId: string, name: string) {
  const { business } = await requireBusiness();
  await assertOwnsCategory(categoryId, business.id);
  const trimmed = name.trim();
  if (!trimmed) throw new Error("Category name is required.");

  await prisma.menuCategory.update({
    where: { id: categoryId },
    data: { name: trimmed },
  });
  revalidateAdmin();
}

export async function deleteCategory(categoryId: string) {
  const { business } = await requireBusiness();
  await assertOwnsCategory(categoryId, business.id);

  await prisma.menuCategory.delete({ where: { id: categoryId } });
  revalidateAdmin();
}

export async function reorderCategories(orderedIds: string[]) {
  const { business } = await requireBusiness();
  const owned = await prisma.menuCategory.findMany({
    where: { businessId: business.id },
    select: { id: true },
  });
  const ownedIds = new Set(owned.map((c) => c.id));
  if (!orderedIds.every((id) => ownedIds.has(id))) {
    throw new Error("Invalid category list.");
  }

  await prisma.$transaction(
    orderedIds.map((id, index) =>
      prisma.menuCategory.update({ where: { id }, data: { order: index } })
    )
  );
  revalidateAdmin();
}

// ---------- Items ----------

export async function createItem(formData: FormData) {
  const { business } = await requireBusiness();
  const categoryId = String(formData.get("categoryId") ?? "");
  await assertOwnsCategory(categoryId, business.id);

  const name = String(formData.get("name") ?? "").trim();
  const description = String(formData.get("description") ?? "").trim();
  const price = Number(formData.get("price"));
  const photo = formData.get("photo") as File | null;

  if (!name) throw new Error("Item name is required.");
  if (!Number.isFinite(price) || price < 0) throw new Error("Enter a valid price.");

  const limit = planLimit(business.plan);
  if (limit !== null) {
    const itemCount = await prisma.menuItem.count({ where: { category: { businessId: business.id } } });
    if (itemCount >= limit) {
      throw new Error(
        `Your Sampler plan is limited to ${limit} menu items. Upgrade to Rush for unlimited items.`
      );
    }
  }

  const photoUrl = photo ? await saveUpload(photo, business.id) : null;

  const last = await prisma.menuItem.findFirst({
    where: { categoryId },
    orderBy: { order: "desc" },
  });

  await prisma.menuItem.create({
    data: {
      categoryId,
      name,
      description: description || null,
      price,
      photoUrl,
      order: (last?.order ?? -1) + 1,
    },
  });
  revalidateAdmin();
}

export async function updateItem(formData: FormData) {
  const { business } = await requireBusiness();
  const itemId = String(formData.get("itemId") ?? "");
  await assertOwnsItem(itemId, business.id);

  const name = String(formData.get("name") ?? "").trim();
  const description = String(formData.get("description") ?? "").trim();
  const price = Number(formData.get("price"));
  const photo = formData.get("photo") as File | null;
  const removePhoto = formData.get("removePhoto") === "on";

  if (!name) throw new Error("Item name is required.");
  if (!Number.isFinite(price) || price < 0) throw new Error("Enter a valid price.");

  const photoUrl = photo && photo.size > 0 ? await saveUpload(photo, business.id) : undefined;

  await prisma.menuItem.update({
    where: { id: itemId },
    data: {
      name,
      description: description || null,
      price,
      ...(photoUrl !== undefined ? { photoUrl } : {}),
      ...(removePhoto ? { photoUrl: null } : {}),
    },
  });
  revalidateAdmin();
}

export async function deleteItem(itemId: string) {
  const { business } = await requireBusiness();
  await assertOwnsItem(itemId, business.id);

  await prisma.menuItem.delete({ where: { id: itemId } });
  revalidateAdmin();
}

export async function toggleSoldOut(itemId: string, soldOut: boolean) {
  const { business } = await requireBusiness();
  await assertOwnsItem(itemId, business.id);

  await prisma.menuItem.update({ where: { id: itemId }, data: { soldOut } });
  revalidateAdmin();
}

export async function reorderItems(categoryId: string, orderedIds: string[]) {
  const { business } = await requireBusiness();
  await assertOwnsCategory(categoryId, business.id);

  const owned = await prisma.menuItem.findMany({
    where: { categoryId },
    select: { id: true },
  });
  const ownedIds = new Set(owned.map((i) => i.id));
  if (!orderedIds.every((id) => ownedIds.has(id))) {
    throw new Error("Invalid item list.");
  }

  await prisma.$transaction(
    orderedIds.map((id, index) =>
      prisma.menuItem.update({ where: { id }, data: { order: index } })
    )
  );
  revalidateAdmin();
}

// ---------- Special banner ----------

export async function updateSpecial(active: boolean, text: string) {
  const { business } = await requireBusiness();
  await prisma.business.update({
    where: { id: business.id },
    data: { specialActive: active, specialText: text.trim() || null },
  });
  revalidateAdmin();
}
