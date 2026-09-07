"use client";

import { useState, useTransition } from "react";
import { ComboData } from "@/lib/types";
import { deleteCombo, toggleComboActive, updateCombo } from "@/lib/actions/combos";
import { Button } from "@/components/ui/button";
import { Input, Label, Textarea } from "@/components/ui/input";
import { formatPrice, cn } from "@/lib/utils";
import { SortableItem, DragHandle } from "@/components/admin/sortable";

export function ComboRow({ combo }: { combo: ComboData }) {
  const [editing, setEditing] = useState(false);
  const [pending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  if (editing) {
    return (
      <div className="rounded-lg border border-orange-200 bg-orange-50/50 p-4">
        <form
          action={(formData) => {
            setError(null);
            formData.set("comboId", combo.id);
            startTransition(async () => {
              try {
                await updateCombo(formData);
                setEditing(false);
              } catch (e) {
                setError(e instanceof Error ? e.message : "Something went wrong.");
              }
            });
          }}
          className="space-y-3"
        >
          <div className="grid gap-3 sm:grid-cols-2">
            <div>
              <Label htmlFor={`combo-name-${combo.id}`}>Name</Label>
              <Input id={`combo-name-${combo.id}`} name="name" defaultValue={combo.name} required />
            </div>
            <div>
              <Label htmlFor={`combo-price-${combo.id}`}>Price ($)</Label>
              <Input
                id={`combo-price-${combo.id}`}
                name="price"
                type="number"
                step="0.01"
                min="0"
                defaultValue={combo.price}
                required
              />
            </div>
          </div>
          <div>
            <Label htmlFor={`combo-desc-${combo.id}`}>Description</Label>
            <Textarea
              id={`combo-desc-${combo.id}`}
              name="description"
              rows={2}
              defaultValue={combo.description ?? ""}
              placeholder="6 bites, crispy fries and 1 dipping sauce."
            />
          </div>
          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex gap-2">
            <Button type="submit" size="sm" disabled={pending}>
              {pending ? "Saving…" : "Save"}
            </Button>
            <Button type="button" variant="secondary" size="sm" onClick={() => setEditing(false)}>
              Cancel
            </Button>
          </div>
        </form>
      </div>
    );
  }

  return (
    <SortableItem id={combo.id}>
      {({ attributes, listeners }) => (
        <div
          className={cn(
            "flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3",
            !combo.active && "opacity-60"
          )}
        >
          <DragHandle attributes={attributes} listeners={listeners} />
          <div className="min-w-0 flex-1">
            <div className="flex items-baseline gap-2">
              <span className="truncate font-medium text-slate-900">{combo.name}</span>
              {!combo.active && (
                <span className="rounded bg-slate-200 px-1.5 py-0.5 text-xs font-semibold text-slate-600">
                  HIDDEN
                </span>
              )}
            </div>
            {combo.description && (
              <p className="truncate text-sm text-slate-500">{combo.description}</p>
            )}
          </div>
          <span className="whitespace-nowrap font-medium text-slate-700">
            {formatPrice(combo.price)}
          </span>
          <Button
            variant={combo.active ? "secondary" : "primary"}
            size="sm"
            disabled={pending}
            onClick={() =>
              startTransition(() => toggleComboActive(combo.id, !combo.active))
            }
          >
            {combo.active ? "Hide" : "Show"}
          </Button>
          <Button variant="ghost" size="sm" onClick={() => setEditing(true)}>
            Edit
          </Button>
          <Button
            variant="ghost"
            size="sm"
            disabled={pending}
            onClick={() => {
              if (confirm(`Delete "${combo.name}"?`)) {
                startTransition(() => deleteCombo(combo.id));
              }
            }}
          >
            Delete
          </Button>
        </div>
      )}
    </SortableItem>
  );
}
