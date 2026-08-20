"use client";

import { useState, useTransition } from "react";
import {
  DndContext,
  closestCenter,
  PointerSensor,
  useSensor,
  useSensors,
  DragEndEvent,
} from "@dnd-kit/core";
import {
  SortableContext,
  verticalListSortingStrategy,
  arrayMove,
} from "@dnd-kit/sortable";
import { CategoryData } from "@/lib/types";
import { deleteCategory, renameCategory, reorderItems } from "@/lib/actions/menu";
import { ItemRow } from "@/components/admin/item-row";
import { AddItemForm } from "@/components/admin/add-item-form";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { SortableItem, DragHandle } from "@/components/admin/sortable";

export function CategoryCard({ category }: { category: CategoryData }) {
  const [prevCategory, setPrevCategory] = useState(category);
  const [items, setItems] = useState(category.items);
  const [renaming, setRenaming] = useState(false);
  const [name, setName] = useState(category.name);
  const [, startTransition] = useTransition();

  if (category !== prevCategory) {
    setPrevCategory(category);
    setItems(category.items);
    setName(category.name);
  }

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 5 } }));

  function handleDragEnd(event: DragEndEvent) {
    const { active, over } = event;
    if (!over || active.id === over.id) return;
    const oldIndex = items.findIndex((i) => i.id === active.id);
    const newIndex = items.findIndex((i) => i.id === over.id);
    const next = arrayMove(items, oldIndex, newIndex);
    setItems(next);
    startTransition(() => reorderItems(category.id, next.map((i) => i.id)));
  }

  return (
    <SortableItem id={category.id}>
      {({ attributes, listeners }) => (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="mb-4 flex items-center gap-2">
            <DragHandle attributes={attributes} listeners={listeners} />
            {renaming ? (
              <form
                className="flex flex-1 gap-2"
                action={() => {
                  startTransition(() => renameCategory(category.id, name));
                  setRenaming(false);
                }}
              >
                <Input value={name} onChange={(e) => setName(e.target.value)} autoFocus />
                <Button type="submit" size="sm">
                  Save
                </Button>
              </form>
            ) : (
              <h2
                className="flex-1 cursor-pointer text-lg font-bold text-slate-900"
                onClick={() => setRenaming(true)}
                title="Click to rename"
              >
                {category.name}
              </h2>
            )}
            <Button
              variant="ghost"
              size="sm"
              onClick={() => {
                if (confirm(`Delete "${category.name}" and all its items?`)) {
                  startTransition(() => deleteCategory(category.id));
                }
              }}
            >
              Delete category
            </Button>
          </div>

          <DndContext
            id={`items-${category.id}`}
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragEnd={handleDragEnd}
          >
            <SortableContext items={items.map((i) => i.id)} strategy={verticalListSortingStrategy}>
              <div className="space-y-2">
                {items.map((item) => (
                  <ItemRow key={item.id} item={item} />
                ))}
              </div>
            </SortableContext>
          </DndContext>

          {items.length === 0 && (
            <p className="mb-3 text-sm text-slate-400">No items yet.</p>
          )}

          <div className="mt-3">
            <AddItemForm categoryId={category.id} />
          </div>
        </div>
      )}
    </SortableItem>
  );
}
