"use client";

import { useState, useTransition } from "react";
import { updateOrientation, updateTheme } from "@/lib/actions/settings";
import { THEME_META, THEMES, ThemeName } from "@/lib/themes";
import { cn } from "@/lib/utils";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

export function ThemePicker({
  initialTheme,
  initialOrientation,
}: {
  initialTheme: ThemeName;
  initialOrientation: "LANDSCAPE" | "PORTRAIT";
}) {
  const [theme, setTheme] = useState(initialTheme);
  const [orientation, setOrientation] = useState(initialOrientation);
  const [, startTransition] = useTransition();

  return (
    <div className="space-y-6">
      <Card>
        <h2 className="mb-1 text-lg font-bold text-slate-900">Display theme</h2>
        <p className="mb-4 text-sm text-slate-500">
          Pick how your menu looks on the screen. You can change this anytime.
        </p>
        <div className="grid gap-4 sm:grid-cols-2">
          {THEMES.map((name) => (
            <button
              key={name}
              type="button"
              onClick={() => {
                setTheme(name);
                startTransition(() => updateTheme(name));
              }}
              className={cn(
                "rounded-2xl border-2 p-4 text-left transition-colors",
                theme === name ? "border-orange-600" : "border-slate-200 hover:border-slate-300"
              )}
            >
              <div
                className="mb-3 h-20 w-full rounded-lg"
                style={{ background: THEME_META[name].swatch }}
              />
              <div className="flex items-center gap-2">
                <span className="font-semibold text-slate-900">{THEME_META[name].label}</span>
                {theme === name && (
                  <span className="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">
                    Selected
                  </span>
                )}
              </div>
              <p className="mt-1 text-sm text-slate-500">{THEME_META[name].blurb}</p>
            </button>
          ))}
        </div>
      </Card>

      <Card>
        <h2 className="mb-1 text-lg font-bold text-slate-900">Screen orientation</h2>
        <p className="mb-4 text-sm text-slate-500">
          Landscape for a normal TV, portrait for a screen mounted upright.
        </p>
        <div className="flex gap-2">
          <Button
            variant={orientation === "LANDSCAPE" ? "primary" : "secondary"}
            onClick={() => {
              setOrientation("LANDSCAPE");
              startTransition(() => updateOrientation("LANDSCAPE"));
            }}
          >
            Landscape
          </Button>
          <Button
            variant={orientation === "PORTRAIT" ? "primary" : "secondary"}
            onClick={() => {
              setOrientation("PORTRAIT");
              startTransition(() => updateOrientation("PORTRAIT"));
            }}
          >
            Portrait
          </Button>
        </div>
      </Card>
    </div>
  );
}
