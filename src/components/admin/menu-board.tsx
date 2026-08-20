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
import { createCategory, reorderCategories } from "@/lib/actions/menu";
import { CategoryCard } from "@/components/admin/category-card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export function MenuBoard({ categories }: { categories: CategoryData[] }) {
  const [prevCategories, setPrevCategories] = useState(categories);
  const [items, setItems] = useState(categories);
  const [newName, setNewName] = useState("");
  const [pending, startTransition] = useTransition();

  if (categories !== prevCategories) {
    setPrevCategories(categories);
    setItems(categories);
  }

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 5 } }));

  function handleDragEnd(event: DragEndEvent) {
    const { active, over } = event;
    if (!over || active.id === over.id) return;
    const oldIndex = items.findIndex((c) => c.id === active.id);
    const newIndex = items.findIndex((c) => c.id === over.id);
    const next = arrayMove(items, oldIndex, newIndex);
    setItems(next);
    startTransition(() => reorderCategories(next.map((c) => c.id)));
  }

  return (
    <div className="space-y-6">
      <DndContext
        id="categories"
        sensors={sensors}
        collisionDetection={closestCenter}
        onDragEnd={handleDragEnd}
      >
        <SortableContext items={items.map((c) => c.id)} strategy={verticalListSortingStrategy}>
          <div className="space-y-6">
            {items.map((category) => (
              <CategoryCard key={category.id} category={category} />
            ))}
          </div>
        </SortableContext>
      </DndContext>

      {items.length === 0 && (
        <p className="text-sm text-slate-400">
          No categories yet — add one below to start building your menu.
        </p>
      )}

      <form
        className="flex gap-2"
        action={() => {
          if (!newName.trim()) return;
          startTransition(() => createCategory(newName));
          setNewName("");
        }}
      >
        <Input
          value={newName}
          onChange={(e) => setNewName(e.target.value)}
          placeholder="New category name (e.g. Desserts)"
        />
        <Button type="submit" disabled={pending}>
          Add category
        </Button>
      </form>
    </div>
  );
}
