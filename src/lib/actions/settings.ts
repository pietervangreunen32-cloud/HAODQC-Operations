"use server";

import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireBusiness } from "@/lib/current-business";
import { saveUpload } from "@/lib/uploads";
import { CUSTOM_FONTS, THEMES, ThemeName, isValidHexColor } from "@/lib/themes";

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

export async function updateCustomBranding(formData: FormData) {
  const { business } = await requireBusiness();

  const primaryColor = String(formData.get("primaryColor") ?? "");
  const backgroundColor = String(formData.get("backgroundColor") ?? "");
  const textColor = String(formData.get("textColor") ?? "");
  const font = String(formData.get("font") ?? "");

  for (const [label, value] of [
    ["Primary color", primaryColor],
    ["Background color", backgroundColor],
    ["Text color", textColor],
  ] as const) {
    if (!isValidHexColor(value)) throw new Error(`${label} must be a valid color.`);
  }
  if (!CUSTOM_FONTS.some((f) => f.value === font)) {
    throw new Error("Unknown font.");
  }

  await prisma.business.update({
    where: { id: business.id },
    data: {
      theme: "CUSTOM",
      customPrimaryColor: primaryColor,
      customBackgroundColor: backgroundColor,
      customTextColor: textColor,
      customFont: font,
    },
  });
  revalidatePath("/admin/theme");
}

export async function updateShowSoldOutItems(hide: boolean) {
  const { business } = await requireBusiness();
  await prisma.business.update({
    where: { id: business.id },
    data: { hideSoldOutItems: hide },
  });
  revalidatePath("/admin/theme");
}
