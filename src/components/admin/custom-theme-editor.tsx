"use client";

import { useState, useTransition } from "react";
import { updateCustomBranding } from "@/lib/actions/settings";
import { CUSTOM_FONTS, customFontCss } from "@/lib/themes";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Label } from "@/components/ui/input";

export function CustomThemeEditor({
  isActive,
  initialPrimaryColor,
  initialBackgroundColor,
  initialTextColor,
  initialFont,
}: {
  isActive: boolean;
  initialPrimaryColor: string;
  initialBackgroundColor: string;
  initialTextColor: string;
  initialFont: string;
}) {
  const [primary, setPrimary] = useState(initialPrimaryColor);
  const [background, setBackground] = useState(initialBackgroundColor);
  const [text, setText] = useState(initialTextColor);
  const [font, setFont] = useState(initialFont || CUSTOM_FONTS[0].value);
  const [pending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  return (
    <Card>
      <div className="mb-1 flex items-center gap-2">
        <h2 className="text-lg font-bold text-slate-900">Custom theme</h2>
        <span className="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">
          Fleet plan
        </span>
        {isActive && (
          <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
            Active
          </span>
        )}
      </div>
      <p className="mb-4 text-sm text-slate-500">
        Match your own brand colors and pick a display font instead of one of the built-in themes.
      </p>

      <form
        action={(formData) => {
          setError(null);
          startTransition(async () => {
            try {
              await updateCustomBranding(formData);
            } catch (e) {
              setError(e instanceof Error ? e.message : "Something went wrong.");
            }
          });
        }}
        className="grid gap-6 sm:grid-cols-2"
      >
        <div className="space-y-4">
          <div>
            <Label htmlFor="primaryColor">Accent color</Label>
            <div className="flex items-center gap-2">
              <input
                id="primaryColor"
                name="primaryColor"
                type="color"
                value={primary}
                onChange={(e) => setPrimary(e.target.value)}
                className="h-10 w-14 cursor-pointer rounded border border-slate-300"
              />
              <span className="font-mono text-sm text-slate-500">{primary}</span>
            </div>
          </div>
          <div>
            <Label htmlFor="backgroundColor">Background color</Label>
            <div className="flex items-center gap-2">
              <input
                id="backgroundColor"
                name="backgroundColor"
                type="color"
                value={background}
                onChange={(e) => setBackground(e.target.value)}
                className="h-10 w-14 cursor-pointer rounded border border-slate-300"
              />
              <span className="font-mono text-sm text-slate-500">{background}</span>
            </div>
          </div>
          <div>
            <Label htmlFor="textColor">Text color</Label>
            <div className="flex items-center gap-2">
              <input
                id="textColor"
                name="textColor"
                type="color"
                value={text}
                onChange={(e) => setText(e.target.value)}
                className="h-10 w-14 cursor-pointer rounded border border-slate-300"
              />
              <span className="font-mono text-sm text-slate-500">{text}</span>
            </div>
          </div>
          <div>
            <Label htmlFor="font">Font</Label>
            <select
              id="font"
              name="font"
              value={font}
              onChange={(e) => setFont(e.target.value)}
              className="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200"
            >
              {CUSTOM_FONTS.map((f) => (
                <option key={f.value} value={f.value}>
                  {f.label}
                </option>
              ))}
            </select>
          </div>

          {error && <p className="text-sm text-red-600">{error}</p>}
          <Button type="submit" disabled={pending}>
            {pending ? "Saving…" : "Use this custom theme"}
          </Button>
        </div>

        <div
          className="flex flex-col justify-center gap-2 rounded-2xl p-6"
          style={{
            background,
            color: text,
            fontFamily: customFontCss(font),
          }}
        >
          <span className="text-xs font-semibold uppercase tracking-wide" style={{ color: primary }}>
            Preview
          </span>
          <span className="text-3xl font-black">Your Business Name</span>
          <div className="mt-2 flex items-center justify-between rounded-xl p-3" style={{ background: `${primary}1a`, border: `1px solid ${primary}4d` }}>
            <span className="font-bold">Sample Menu Item</span>
            <span className="font-bold" style={{ color: primary }}>
              $9.00
            </span>
          </div>
        </div>
      </form>
    </Card>
  );
}
