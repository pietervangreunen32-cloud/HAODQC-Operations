"use client";

import { useState, useTransition } from "react";
import { updateSpecial } from "@/lib/actions/menu";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card } from "@/components/ui/card";

export function SpecialBanner({
  initialActive,
  initialText,
}: {
  initialActive: boolean;
  initialText: string;
}) {
  const [active, setActive] = useState(initialActive);
  const [text, setText] = useState(initialText);
  const [pending, startTransition] = useTransition();

  return (
    <Card>
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-bold text-slate-900">Today&apos;s special</h2>
        <label className="flex items-center gap-2 text-sm font-medium text-slate-600">
          <input
            type="checkbox"
            className="h-4 w-4 rounded"
            checked={active}
            onChange={(e) => {
              setActive(e.target.checked);
              startTransition(() => updateSpecial(e.target.checked, text));
            }}
          />
          Show on display
        </label>
      </div>
      <form
        className="mt-3 flex gap-2"
        action={() => startTransition(() => updateSpecial(active, text))}
      >
        <Input
          value={text}
          onChange={(e) => setText(e.target.value)}
          placeholder="e.g. Buy 2 tacos, get a free drink!"
        />
        <Button type="submit" variant="secondary" disabled={pending}>
          Save
        </Button>
      </form>
    </Card>
  );
}
