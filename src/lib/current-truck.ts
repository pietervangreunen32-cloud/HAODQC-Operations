import { redirect } from "next/navigation";
import { auth } from "@/auth";
import { prisma } from "@/lib/prisma";

export async function requireTruck() {
  const session = await auth();
  if (!session?.user?.id) redirect("/login");

  const truck = await prisma.truck.findFirst({
    where: { ownerId: session.user.id },
  });
  if (!truck) redirect("/login");

  return { session, truck };
}

export async function requireTruckWithMenu() {
  const { session, truck: truckShallow } = await requireTruck();

  const truck = await prisma.truck.findUniqueOrThrow({
    where: { id: truckShallow.id },
    include: {
      categories: {
        orderBy: { order: "asc" },
        include: { items: { orderBy: { order: "asc" } } },
      },
    },
  });

  return { session, truck };
}
