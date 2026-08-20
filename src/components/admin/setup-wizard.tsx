"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { CategoryData } from "@/lib/types";
import { ThemeName } from "@/lib/themes";
import { MenuBoard } from "@/components/admin/menu-board";
import { ThemePicker } from "@/components/admin/theme-picker";
import { CopyLinkButton } from "@/components/admin/copy-link-button";
import { completeOnboarding } from "@/lib/actions/settings";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";

const STEPS = ["Add your items", "Pick a look", "Get your link"] as const;

export function SetupWizard({
  categories,
  theme,
  orientation,
  displayUrl,
  qrDataUrl,
}: {
  categories: CategoryData[];
  theme: ThemeName;
  orientation: "LANDSCAPE" | "PORTRAIT";
  displayUrl: string;
  qrDataUrl: string;
}) {
  const [step, setStep] = useState(0);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  return (
    <div className="space-y-6">
      <ol className="flex gap-2">
        {STEPS.map((label, i) => (
          <li
            key={label}
            className={`flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium ${
              i === step
                ? "bg-orange-600 text-white"
                : i < step
                  ? "bg-orange-100 text-orange-700"
                  : "bg-slate-100 text-slate-400"
            }`}
          >
            {i + 1}. {label}
          </li>
        ))}
      </ol>

      {step === 0 && (
        <Card>
          <h1 className="mb-1 text-xl font-bold text-slate-900">Add your first few items</h1>
          <p className="mb-4 text-sm text-slate-500">
            We&apos;ve started you off with Mains, Sides, and Drinks categories — rename or
            delete them however you like. Add at least one item to continue.
          </p>
          <MenuBoard categories={categories} />
        </Card>
      )}

      {step === 1 && (
        <div>
          <h1 className="mb-1 text-xl font-bold text-slate-900">Pick a look</h1>
          <p className="mb-4 text-sm text-slate-500">You can change this anytime later.</p>
          <ThemePicker initialTheme={theme} initialOrientation={orientation} />
        </div>
      )}

      {step === 2 && (
        <Card>
          <h1 className="mb-1 text-xl font-bold text-slate-900">Your display is ready</h1>
          <p className="mb-4 text-sm text-slate-500">
            Open this link on the screen mounted on your truck — it updates automatically
            whenever you change your menu.
          </p>
          <div className="flex flex-col items-center gap-4 sm:flex-row">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={qrDataUrl} alt="QR code for your display" width={180} height={180} />
            <div className="flex-1">
              <p className="mb-3 break-all rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm">
                {displayUrl}
              </p>
              <div className="flex flex-wrap gap-2">
                <CopyLinkButton url={displayUrl} />
                <Link href={displayUrl} target="_blank">
                  <Button variant="secondary">Preview ↗</Button>
                </Link>
              </div>
            </div>
          </div>
          <p className="mt-4 text-sm text-slate-500">
            Need help getting it onto an actual TV or Fire Stick?{" "}
            <Link href="/admin/help" className="font-medium text-orange-600 hover:underline">
              See the how-to guide
            </Link>
            .
          </p>
        </Card>
      )}

      <div className="flex justify-between">
        <Button
          variant="secondary"
          onClick={() => setStep((s) => Math.max(0, s - 1))}
          disabled={step === 0}
        >
          Back
        </Button>
        {step < STEPS.length - 1 ? (
          <Button onClick={() => setStep((s) => s + 1)}>Next</Button>
        ) : (
          <Button
            disabled={pending}
            onClick={() =>
              startTransition(async () => {
                await completeOnboarding();
                router.push("/admin");
              })
            }
          >
            {pending ? "Finishing…" : "Finish setup"}
          </Button>
        )}
      </div>
    </div>
  );
}
