"use client";

import { useState, useTransition } from "react";
import Image from "next/image";
import { ItemData } from "@/lib/types";
import { deleteItem, toggleSoldOut, updateItem } from "@/lib/actions/menu";
import { Button } from "@/components/ui/button";
import { Input, Label, Textarea } from "@/components/ui/input";
import { formatPrice, cn } from "@/lib/utils";
import { SortableItem, DragHandle } from "@/components/admin/sortable";

export function ItemRow({ item }: { item: ItemData }) {
  const [editing, setEditing] = useState(false);
  const [pending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  if (editing) {
    return (
      <div className="rounded-lg border border-orange-200 bg-orange-50/50 p-4">
        <form
          action={(formData) => {
            setError(null);
            formData.set("itemId", item.id);
            startTransition(async () => {
              try {
                await updateItem(formData);
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
              <Label htmlFor={`name-${item.id}`}>Name</Label>
              <Input id={`name-${item.id}`} name="name" defaultValue={item.name} required />
            </div>
            <div>
              <Label htmlFor={`price-${item.id}`}>Price ($)</Label>
              <Input
                id={`price-${item.id}`}
                name="price"
                type="number"
                step="0.01"
                min="0"
                defaultValue={item.price}
                required
              />
            </div>
          </div>
          <div>
            <Label htmlFor={`desc-${item.id}`}>Description (optional)</Label>
            <Textarea
              id={`desc-${item.id}`}
              name="description"
              rows={2}
              defaultValue={item.description ?? ""}
            />
          </div>
          <div>
            <Label htmlFor={`photo-${item.id}`}>Photo (optional)</Label>
            <Input id={`photo-${item.id}`} name="photo" type="file" accept="image/png,image/jpeg,image/webp" />
            {item.photoUrl && (
              <label className="mt-2 flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="removePhoto" className="rounded" />
                Remove current photo
              </label>
            )}
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
    <SortableItem id={item.id}>
      {({ attributes, listeners }) => (
        <div
          className={cn(
            "flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3",
            item.soldOut && "opacity-60"
          )}
        >
          <DragHandle attributes={attributes} listeners={listeners} />
          {item.photoUrl ? (
            <Image
              src={item.photoUrl}
              alt=""
              width={48}
              height={48}
              className="h-12 w-12 rounded-md object-cover"
            />
          ) : (
            <div className="h-12 w-12 rounded-md bg-slate-100" />
          )}
          <div className="min-w-0 flex-1">
            <div className="flex items-baseline gap-2">
              <span className="truncate font-medium text-slate-900">{item.name}</span>
              {item.soldOut && (
                <span className="rounded bg-red-100 px-1.5 py-0.5 text-xs font-semibold text-red-700">
                  SOLD OUT
                </span>
              )}
            </div>
            {item.description && (
              <p className="truncate text-sm text-slate-500">{item.description}</p>
            )}
          </div>
          <span className="whitespace-nowrap font-medium text-slate-700">
            {formatPrice(item.price)}
          </span>
          <Button
            variant={item.soldOut ? "secondary" : "danger"}
            size="sm"
            disabled={pending}
            onClick={() =>
              startTransition(() => toggleSoldOut(item.id, !item.soldOut))
            }
          >
            {item.soldOut ? "Mark available" : "Sold out"}
          </Button>
          <Button variant="ghost" size="sm" onClick={() => setEditing(true)}>
            Edit
          </Button>
          <Button
            variant="ghost"
            size="sm"
            disabled={pending}
            onClick={() => {
              if (confirm(`Delete "${item.name}"?`)) {
                startTransition(() => deleteItem(item.id));
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
