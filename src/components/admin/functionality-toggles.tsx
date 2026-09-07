"use client";

import { useState, useTransition } from "react";
import { updateShowSoldOutItems } from "@/lib/actions/settings";
import { Card } from "@/components/ui/card";

export function FunctionalityToggles({ initialHideSoldOut }: { initialHideSoldOut: boolean }) {
  const [hideSoldOut, setHideSoldOut] = useState(initialHideSoldOut);
  const [, startTransition] = useTransition();

  return (
    <Card>
      <h2 className="mb-1 text-lg font-bold text-slate-900">Display behavior</h2>
      <p className="mb-4 text-sm text-slate-500">Fine-tune how the display handles sold-out items.</p>
      <label className="flex items-start gap-3">
        <input
          type="checkbox"
          className="mt-1 h-4 w-4 rounded"
          checked={hideSoldOut}
          onChange={(e) => {
            setHideSoldOut(e.target.checked);
            startTransition(() => updateShowSoldOutItems(e.target.checked));
          }}
        />
        <span>
          <span className="block text-sm font-medium text-slate-900">Hide sold-out items completely</span>
          <span className="block text-sm text-slate-500">
            Off by default: sold-out items stay visible with a &quot;SOLD OUT&quot; badge. Turn this
            on to remove them from the display entirely instead.
          </span>
        </span>
      </label>
    </Card>
  );
}
