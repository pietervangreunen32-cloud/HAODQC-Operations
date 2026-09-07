"use server";

import Papa from "papaparse";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireBusiness } from "@/lib/current-business";
import { planAtLeast, planLimit } from "@/lib/plans";

type CsvRow = {
  category?: string;
  name?: string;
  description?: string;
  price?: string;
  sold_out?: string;
};

export type CsvImportState =
  | { error: string; summary?: undefined }
  | {
      summary: {
        itemsCreated: number;
        categoriesCreated: number;
        skipped: { row: number; reason: string }[];
      };
      error?: undefined;
    }
  | undefined;

const REQUIRED_HEADERS = ["category", "name", "price"];

export async function importMenuCsv(
  _prevState: CsvImportState,
  formData: FormData
): Promise<CsvImportState> {
  const { business } = await requireBusiness();
  if (!planAtLeast(business.plan, "RUSH")) {
    return { error: "Bulk CSV import requires the Rush plan or higher. Upgrade to import many items at once." };
  }

  const file = formData.get("csv") as File | null;
  if (!file || file.size === 0) {
    return { error: "Choose a CSV file first." };
  }

  const text = await file.text();
  const parsed = Papa.parse<CsvRow>(text, {
    header: true,
    skipEmptyLines: true,
    transformHeader: (h) => h.trim().toLowerCase(),
  });

  const headers = parsed.meta.fields?.map((h) => h.toLowerCase()) ?? [];
  const missing = REQUIRED_HEADERS.filter((h) => !headers.includes(h));
  if (missing.length > 0) {
    return { error: `Missing required column${missing.length > 1 ? "s" : ""}: ${missing.join(", ")}.` };
  }

  const limit = planLimit(business.plan);
  const existingItemCount = limit !== null
    ? await prisma.menuItem.count({ where: { category: { businessId: business.id } } })
    : 0;

  const categoryCache = new Map<string, { id: string; nextOrder: number }>();
  const existingCategories = await prisma.menuCategory.findMany({
    where: { businessId: business.id },
    orderBy: { order: "asc" },
  });
  for (const cat of existingCategories) {
    const last = await prisma.menuItem.findFirst({
      where: { categoryId: cat.id },
      orderBy: { order: "desc" },
    });
    categoryCache.set(cat.name.toLowerCase(), { id: cat.id, nextOrder: (last?.order ?? -1) + 1 });
  }
  let nextCategoryOrder = existingCategories.length
    ? Math.max(...existingCategories.map((c) => c.order)) + 1
    : 0;

  let itemsCreated = 0;
  let categoriesCreated = 0;
  const skipped: { row: number; reason: string }[] = [];

  for (let i = 0; i < parsed.data.length; i++) {
    const row = parsed.data[i];
    const rowNumber = i + 2; // account for header row + 1-indexing

    const categoryName = (row.category ?? "").trim();
    const name = (row.name ?? "").trim();
    const price = Number(row.price);
    const soldOut = ["true", "1", "yes"].includes((row.sold_out ?? "").trim().toLowerCase());
    const description = (row.description ?? "").trim();

    if (!categoryName || !name) {
      skipped.push({ row: rowNumber, reason: "Missing category or name." });
      continue;
    }
    if (!Number.isFinite(price) || price < 0) {
      skipped.push({ row: rowNumber, reason: `Invalid price "${row.price ?? ""}".` });
      continue;
    }
    if (limit !== null && existingItemCount + itemsCreated >= limit) {
      skipped.push({ row: rowNumber, reason: `Plan limit reached (${limit} items).` });
      continue;
    }

    let cat = categoryCache.get(categoryName.toLowerCase());
    if (!cat) {
      const created = await prisma.menuCategory.create({
        data: { businessId: business.id, name: categoryName, order: nextCategoryOrder++ },
      });
      cat = { id: created.id, nextOrder: 0 };
      categoryCache.set(categoryName.toLowerCase(), cat);
      categoriesCreated++;
    }

    await prisma.menuItem.create({
      data: {
        categoryId: cat.id,
        name,
        description: description || null,
        price,
        soldOut,
        order: cat.nextOrder,
      },
    });
    cat.nextOrder++;
    itemsCreated++;
  }

  revalidatePath("/admin");
  return { summary: { itemsCreated, categoriesCreated, skipped } };
}
