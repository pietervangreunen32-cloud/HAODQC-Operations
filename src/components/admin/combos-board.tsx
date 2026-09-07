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
import { PlanName, planAtLeast } from "@/lib/plans";

export function CombosBoard({
  combos,
  initialShowOnDisplay,
  plan,
}: {
  combos: ComboData[];
  initialShowOnDisplay: boolean;
  plan: PlanName;
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
  const canUseCombos = planAtLeast(plan, "RUSH");

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
            <div className="flex items-center gap-2">
              <h2 className="text-lg font-bold text-slate-900">Combos &amp; Upsells</h2>
              {!canUseCombos && (
                <span className="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">
                  Rush plan
                </span>
              )}
            </div>
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

      {canUseCombos ? (
        <AddComboForm />
      ) : (
        <Card>
          <p className="text-sm text-slate-600">
            Combos &amp; upsells are a Rush plan feature.{" "}
            <a href="/admin/upgrade" className="font-medium text-orange-600 hover:underline">
              Upgrade to Rush
            </a>{" "}
            to start bundling items.
          </p>
        </Card>
      )}
    </div>
  );
}
