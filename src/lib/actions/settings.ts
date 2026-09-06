"use server";

import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireBusiness } from "@/lib/current-business";
import { saveUpload } from "@/lib/uploads";
import { THEMES, ThemeName } from "@/lib/themes";

export async function updateTheme(theme: ThemeName) {
  const { business } = await requireBusiness();
  if (!THEMES.includes(theme)) throw new Error("Unknown theme.");

  await prisma.business.update({ where: { id: business.id }, data: { theme } });
  revalidatePath("/admin/theme");
}

export async function updateOrientation(orientation: "LANDSCAPE" | "PORTRAIT") {
  const { business } = await requireBusiness();
  await prisma.business.update({ where: { id: business.id }, data: { orientation } });
  revalidatePath("/admin/theme");
}

export async function updateLogo(formData: FormData) {
  const { business } = await requireBusiness();
  const logo = formData.get("logo") as File | null;
  if (!logo || logo.size === 0) return;

  const logoUrl = await saveUpload(logo, business.id);
  await prisma.business.update({ where: { id: business.id }, data: { logoUrl } });
  revalidatePath("/admin/theme");
}

export async function completeOnboarding() {
  const { business } = await requireBusiness();
  await prisma.business.update({
    where: { id: business.id },
    data: { onboardedAt: new Date() },
  });
}
