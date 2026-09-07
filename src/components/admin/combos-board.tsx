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
import { ComboData } from "@/lib/types";
import { reorderCombos, updateShowCombosOnDisplay } from "@/lib/actions/combos";
import { ComboRow } from "@/components/admin/combo-row";
import { AddComboForm } from "@/components/admin/add-combo-form";
import { Card } from "@/components/ui/card";

export function CombosBoard({
  combos,
  initialShowOnDisplay,
}: {
  combos: ComboData[];
  initialShowOnDisplay: boolean;
}) {
  const [prevCombos, setPrevCombos] = useState(combos);
  const [items, setItems] = useState(combos);
  const [showOnDisplay, setShowOnDisplay] = useState(initialShowOnDisplay);
  const [, startTransition] = useTransition();

  if (combos !== prevCombos) {
    setPrevCombos(combos);
    setItems(combos);
  }

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 5 } }));

  function handleDragEnd(event: DragEndEvent) {
    const { active, over } = event;
    if (!over || active.id === over.id) return;
    const oldIndex = items.findIndex((c) => c.id === active.id);
    const newIndex = items.findIndex((c) => c.id === over.id);
    const next = arrayMove(items, oldIndex, newIndex);
    setItems(next);
    startTransition(() => reorderCombos(next.map((c) => c.id)));
  }

  return (
    <div className="space-y-6">
      <Card>
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-lg font-bold text-slate-900">Combos &amp; Upsells</h2>
            <p className="text-sm text-slate-500">
              Bundle items together at a set price — shown as their own section on your display.
            </p>
          </div>
          <label className="flex items-center gap-2 text-sm font-medium text-slate-600">
            <input
              type="checkbox"
              className="h-4 w-4 rounded"
              checked={showOnDisplay}
              onChange={(e) => {
                setShowOnDisplay(e.target.checked);
                startTransition(() => updateShowCombosOnDisplay(e.target.checked));
              }}
            />
            Show on display
          </label>
        </div>
      </Card>

      <DndContext id="combos" sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
        <SortableContext items={items.map((c) => c.id)} strategy={verticalListSortingStrategy}>
          <div className="space-y-2">
            {items.map((combo) => (
              <ComboRow key={combo.id} combo={combo} />
            ))}
          </div>
        </SortableContext>
      </DndContext>

      {items.length === 0 && (
        <p className="text-sm text-slate-400">
          No combos yet — add one below (e.g. &quot;Crunch Combo: 6 bites + fries + a dip&quot;).
        </p>
      )}

      <AddComboForm />
    </div>
  );
}
