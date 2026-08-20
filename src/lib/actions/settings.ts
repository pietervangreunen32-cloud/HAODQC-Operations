"use server";

import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireTruck } from "@/lib/current-truck";
import { saveUpload } from "@/lib/uploads";
import { THEMES, ThemeName } from "@/lib/themes";

export async function updateTheme(theme: ThemeName) {
  const { truck } = await requireTruck();
  if (!THEMES.includes(theme)) throw new Error("Unknown theme.");

  await prisma.truck.update({ where: { id: truck.id }, data: { theme } });
  revalidatePath("/admin/theme");
}

export async function updateOrientation(orientation: "LANDSCAPE" | "PORTRAIT") {
  const { truck } = await requireTruck();
  await prisma.truck.update({ where: { id: truck.id }, data: { orientation } });
  revalidatePath("/admin/theme");
}

export async function updateLogo(formData: FormData) {
  const { truck } = await requireTruck();
  const logo = formData.get("logo") as File | null;
  if (!logo || logo.size === 0) return;

  const logoUrl = await saveUpload(logo, truck.id);
  await prisma.truck.update({ where: { id: truck.id }, data: { logoUrl } });
  revalidatePath("/admin/theme");
}

export async function completeOnboarding() {
  const { truck } = await requireTruck();
  await prisma.truck.update({
    where: { id: truck.id },
    data: { onboardedAt: new Date() },
  });
}
