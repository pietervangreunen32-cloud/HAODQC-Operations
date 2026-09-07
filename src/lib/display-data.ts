import { prisma } from "@/lib/prisma";

export async function getDisplayData(slug: string) {
  const business = await prisma.business.findUnique({
    where: { slug },
    include: {
      categories: {
        orderBy: { order: "asc" },
        include: { items: { orderBy: { order: "asc" } } },
      },
      combos: {
        where: { active: true },
        orderBy: { order: "asc" },
      },
    },
  });
  if (!business) return null;

  return {
    name: business.name,
    slug: business.slug,
    theme: business.theme,
    orientation: business.orientation,
    logoUrl: business.logoUrl,
    specialActive: business.specialActive,
    specialText: business.specialText,
    updatedAt: business.updatedAt.toISOString(),
    custom: {
      primaryColor: business.customPrimaryColor,
      backgroundColor: business.customBackgroundColor,
      textColor: business.customTextColor,
      font: business.customFont,
    },
    categories: business.categories.map((c) => ({
      id: c.id,
      name: c.name,
      items: c.items
        .filter((i) => !(business.hideSoldOutItems && i.soldOut))
        .map((i) => ({
          id: i.id,
          name: i.name,
          description: i.description,
          price: i.price,
          photoUrl: i.photoUrl,
          soldOut: i.soldOut,
        })),
    })),
    combos:
      business.showCombosOnDisplay
        ? business.combos.map((c) => ({
            id: c.id,
            name: c.name,
            description: c.description,
            price: c.price,
          }))
        : [],
  };
}

export type DisplayData = NonNullable<Awaited<ReturnType<typeof getDisplayData>>>;
