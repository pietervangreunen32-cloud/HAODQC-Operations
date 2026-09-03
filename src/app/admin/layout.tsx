import Link from "next/link";
import { requireTruck } from "@/lib/current-truck";
import { logoutAction } from "@/lib/actions/auth";
import { Button } from "@/components/ui/button";

const NAV_ITEMS = [
  { href: "/admin", label: "Menu" },
  { href: "/admin/theme", label: "Theme" },
  { href: "/admin/display", label: "Display & QR" },
  { href: "/admin/help", label: "Put it on a TV" },
];

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const { truck } = await requireTruck();

  return (
    <div className="min-h-screen bg-slate-50">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
          <div className="flex items-center gap-2">
            <span className="text-lg font-bold text-slate-900">MenuScreen</span>
            <span className="hidden text-sm text-slate-400 sm:inline">/ {truck.name}</span>
          </div>
          <nav className="flex flex-wrap items-center gap-1">
            {NAV_ITEMS.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
              >
                {item.label}
              </Link>
            ))}
            <form action={logoutAction}>
              <Button type="submit" variant="ghost" size="sm">
                Log out
              </Button>
            </form>
          </nav>
        </div>
      </header>
      <main className="mx-auto max-w-5xl px-4 py-8">{children}</main>
    </div>
  );
}
