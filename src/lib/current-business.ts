import { redirect } from "next/navigation";
import { auth } from "@/auth";
import { prisma } from "@/lib/prisma";

export async function requireBusiness() {
  const session = await auth();
  if (!session?.user?.id) redirect("/login");

  const business = await prisma.business.findFirst({
    where: { ownerId: session.user.id },
  });
  if (!business) redirect("/login");

  return { session, business };
}

export async function requireBusinessWithMenu() {
  const { session, business: businessShallow } = await requireBusiness();

  const business = await prisma.business.findUniqueOrThrow({
    where: { id: businessShallow.id },
    include: {
      categories: {
        orderBy: { order: "asc" },
        include: { items: { orderBy: { order: "asc" } } },
      },
    },
  });

  return { session, business };
}
