import Link from "next/link";
import { Button } from "@/components/ui/button";

const STEPS = [
  {
    title: "1. Add your items",
    body: "Type in your menu — name, price, photo, category. No design skills needed.",
  },
  {
    title: "2. Pick a look",
    body: "Choose from a few ready-made themes: neon, chalkboard, minimalist, colorful.",
  },
  {
    title: "3. Open it on your TV",
    body: "Get one link and QR code. Open it in any browser — a Fire Stick, an old tablet, a cheap Android box, anything with a screen.",
  },
];

export default function Home() {
  return (
    <main className="min-h-screen bg-slate-50">
      <div className="mx-auto max-w-5xl px-6 py-16 text-center">
        <p className="mb-3 text-sm font-semibold uppercase tracking-wide text-orange-600">
          For food truck owners
        </p>
        <h1 className="text-4xl font-black tracking-tight text-slate-900 sm:text-6xl">
          Turn any screen into a{" "}
          <span className="text-orange-600">live menu board</span>
        </h1>
        <p className="mx-auto mt-5 max-w-2xl text-lg text-slate-600">
          Manage your menu from your phone. It updates on your truck&apos;s screen in
          seconds — sold out items, daily specials, new prices, all without touching
          the TV.
        </p>
        <div className="mt-8 flex justify-center gap-3">
          <Link href="/signup">
            <Button size="lg">Get started free</Button>
          </Link>
          <Link href="/login">
            <Button size="lg" variant="secondary">
              Log in
            </Button>
          </Link>
        </div>

        <div className="mt-20 grid gap-6 text-left sm:grid-cols-3">
          {STEPS.map((step) => (
            <div key={step.title} className="rounded-2xl border border-slate-200 bg-white p-6">
              <h2 className="font-bold text-slate-900">{step.title}</h2>
              <p className="mt-2 text-sm text-slate-600">{step.body}</p>
            </div>
          ))}
        </div>

        <p className="mt-16 text-sm text-slate-400">
          No app installs. No special hardware. Just a link on a screen.
        </p>
      </div>
    </main>
  );
}
