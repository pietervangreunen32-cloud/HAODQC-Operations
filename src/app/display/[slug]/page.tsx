import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { getDisplayData } from "@/lib/display-data";
import { DisplayView } from "@/components/display/display-view";

export default async function DisplayPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const data = await getDisplayData(slug);
  if (!data) notFound();

  // Fire-and-forget view counter — a rough proxy for how often this screen
  // has loaded. Don't await it on the critical path to first paint.
  prisma.truck
    .update({ where: { slug }, data: { viewCount: { increment: 1 } } })
    .catch(() => {});

  return <DisplayView slug={slug} initialData={data} />;
}
