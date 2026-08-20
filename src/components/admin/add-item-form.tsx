"use client";

import { useRef, useState, useTransition } from "react";
import { createItem } from "@/lib/actions/menu";
import { Button } from "@/components/ui/button";
import { Input, Label, Textarea } from "@/components/ui/input";

export function AddItemForm({ categoryId }: { categoryId: string }) {
  const [open, setOpen] = useState(false);
  const [pending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);
  const formRef = useRef<HTMLFormElement>(null);

  if (!open) {
    return (
      <Button variant="secondary" size="sm" onClick={() => setOpen(true)}>
        + Add item
      </Button>
    );
  }

  return (
    <form
      ref={formRef}
      action={(formData) => {
        setError(null);
        formData.set("categoryId", categoryId);
        startTransition(async () => {
          try {
            await createItem(formData);
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
          <Label htmlFor={`new-name-${categoryId}`}>Name</Label>
          <Input id={`new-name-${categoryId}`} name="name" required placeholder="Carne Asada Taco" />
        </div>
        <div>
          <Label htmlFor={`new-price-${categoryId}`}>Price ($)</Label>
          <Input
            id={`new-price-${categoryId}`}
            name="price"
            type="number"
            step="0.01"
            min="0"
            required
            placeholder="4.50"
          />
        </div>
      </div>
      <div>
        <Label htmlFor={`new-desc-${categoryId}`}>Description (optional)</Label>
        <Textarea id={`new-desc-${categoryId}`} name="description" rows={2} />
      </div>
      <div>
        <Label htmlFor={`new-photo-${categoryId}`}>Photo (optional)</Label>
        <Input
          id={`new-photo-${categoryId}`}
          name="photo"
          type="file"
          accept="image/png,image/jpeg,image/webp"
        />
      </div>
      {error && <p className="text-sm text-red-600">{error}</p>}
      <div className="flex gap-2">
        <Button type="submit" size="sm" disabled={pending}>
          {pending ? "Adding…" : "Add item"}
        </Button>
        <Button type="button" variant="secondary" size="sm" onClick={() => setOpen(false)}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
