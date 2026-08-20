import { prisma } from "@/lib/prisma";

export async function getDisplayData(slug: string) {
  const truck = await prisma.truck.findUnique({
    where: { slug },
    include: {
      categories: {
        orderBy: { order: "asc" },
        include: { items: { orderBy: { order: "asc" } } },
      },
    },
  });
  if (!truck) return null;

  return {
    name: truck.name,
    slug: truck.slug,
    theme: truck.theme,
    orientation: truck.orientation,
    logoUrl: truck.logoUrl,
    specialActive: truck.specialActive,
    specialText: truck.specialText,
    updatedAt: truck.updatedAt.toISOString(),
    categories: truck.categories.map((c) => ({
      id: c.id,
      name: c.name,
      items: c.items.map((i) => ({
        id: i.id,
        name: i.name,
        description: i.description,
        price: i.price,
        photoUrl: i.photoUrl,
        soldOut: i.soldOut,
      })),
    })),
  };
}

export type DisplayData = NonNullable<Awaited<ReturnType<typeof getDisplayData>>>;
