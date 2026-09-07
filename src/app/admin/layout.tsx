import Link from "next/link";
import { requireBusiness } from "@/lib/current-business";
import { logoutAction } from "@/lib/actions/auth";
import { Button } from "@/components/ui/button";

const NAV_ITEMS = [
  { href: "/admin/dashboard", label: "Dashboard" },
  { href: "/admin", label: "Menu" },
  { href: "/admin/combos", label: "Combos" },
  { href: "/admin/theme", label: "Theme & Settings" },
  { href: "/admin/display", label: "Display & QR" },
  { href: "/admin/help", label: "Put it on a TV" },
  { href: "/admin/upgrade", label: "Plans & billing" },
];

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const { business } = await requireBusiness();

  return (
    <div className="min-h-screen bg-slate-50">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
          <div className="flex items-center gap-2">
            <span className="text-lg font-bold text-slate-900">MenuScreen</span>
            <span className="hidden text-sm text-slate-400 sm:inline">/ {business.name}</span>
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
