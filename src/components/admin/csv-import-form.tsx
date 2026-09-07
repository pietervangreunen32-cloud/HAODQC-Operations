"use client";

import { useActionState, useState } from "react";
import { importMenuCsv } from "@/lib/actions/csv-import";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";

export function CsvImportForm() {
  const [open, setOpen] = useState(false);
  const [state, formAction, pending] = useActionState(importMenuCsv, undefined);

  if (!open) {
    return (
      <Button variant="secondary" onClick={() => setOpen(true)}>
        Import from CSV
      </Button>
    );
  }

  return (
    <Card className="w-full basis-full">
      <h2 className="mb-1 text-lg font-bold text-slate-900">Import menu items from CSV</h2>
      <p className="mb-4 text-sm text-slate-500">
        Columns: <code>category, name, description, price, sold_out</code>. Categories that
        don&apos;t exist yet are created automatically.{" "}
        <a href="/menu-import-template.csv" download className="font-medium text-orange-600 hover:underline">
          Download a template
        </a>
        .
      </p>

      <form action={formAction} className="space-y-3">
        <input
          type="file"
          name="csv"
          accept=".csv,text/csv"
          required
          className="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-orange-700 hover:file:bg-orange-100"
        />

        {state?.error && (
          <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{state.error}</p>
        )}

        {state?.summary && (
          <div className="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            <p>
              Added {state.summary.itemsCreated} item{state.summary.itemsCreated === 1 ? "" : "s"}
              {state.summary.categoriesCreated > 0 &&
                ` and ${state.summary.categoriesCreated} new categor${state.summary.categoriesCreated === 1 ? "y" : "ies"}`}
              .
            </p>
            {state.summary.skipped.length > 0 && (
              <div className="mt-2">
                <p className="font-medium">
                  Skipped {state.summary.skipped.length} row{state.summary.skipped.length === 1 ? "" : "s"}:
                </p>
                <ul className="mt-1 list-disc space-y-0.5 pl-4">
                  {state.summary.skipped.slice(0, 10).map((s, i) => (
                    <li key={i}>
                      Row {s.row}: {s.reason}
                    </li>
                  ))}
                </ul>
                {state.summary.skipped.length > 10 && (
                  <p className="mt-1">…and {state.summary.skipped.length - 10} more.</p>
                )}
              </div>
            )}
          </div>
        )}

        <div className="flex gap-2">
          <Button type="submit" size="sm" disabled={pending}>
            {pending ? "Importing…" : "Import"}
          </Button>
          <Button type="button" variant="secondary" size="sm" onClick={() => setOpen(false)}>
            Close
          </Button>
        </div>
      </form>
    </Card>
  );
}
