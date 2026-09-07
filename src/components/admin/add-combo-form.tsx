"use client";

import { useRef, useState, useTransition } from "react";
import { createCombo } from "@/lib/actions/combos";
import { Button } from "@/components/ui/button";
import { Input, Label, Textarea } from "@/components/ui/input";

export function AddComboForm() {
  const [open, setOpen] = useState(false);
  const [pending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);
  const formRef = useRef<HTMLFormElement>(null);

  if (!open) {
    return (
      <Button onClick={() => setOpen(true)}>+ Add combo</Button>
    );
  }

  return (
    <form
      ref={formRef}
      action={(formData) => {
        setError(null);
        startTransition(async () => {
          try {
            await createCombo(formData);
            formRef.current?.reset();
            setOpen(false);
          } catch (e) {
            setError(e instanceof Error ? e.message : "Something went wrong.");
          }
        });
      }}
      className="space-y-3 rounded-lg border border-dashed border-slate-300 p-4"
    >
      <div className="grid gap-3 sm:grid-cols-2">
        <div>
          <Label htmlFor="new-combo-name">Name</Label>
          <Input id="new-combo-name" name="name" required placeholder="Crunch Combo" />
        </div>
        <div>
          <Label htmlFor="new-combo-price">Price ($)</Label>
          <Input id="new-combo-price" name="price" type="number" step="0.01" min="0" required placeholder="12.00" />
        </div>
      </div>
      <div>
        <Label htmlFor="new-combo-desc">Description</Label>
        <Textarea
          id="new-combo-desc"
          name="description"
          rows={2}
          placeholder="6 bites, crispy fries and 1 dipping sauce."
        />
      </div>
      {error && <p className="text-sm text-red-600">{error}</p>}
      <div className="flex gap-2">
        <Button type="submit" size="sm" disabled={pending}>
          {pending ? "Adding…" : "Add combo"}
        </Button>
        <Button type="button" variant="secondary" size="sm" onClick={() => setOpen(false)}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
