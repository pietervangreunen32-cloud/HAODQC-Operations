"use client";

import { useState, useTransition } from "react";
import Image from "next/image";
import { updateLogo } from "@/lib/actions/settings";
import { Button } from "@/components/ui/button";
import { Input, Label } from "@/components/ui/input";
import { Card } from "@/components/ui/card";

export function LogoUploader({ initialLogoUrl }: { initialLogoUrl: string | null }) {
  const [logoUrl, setLogoUrl] = useState(initialLogoUrl);
  const [pending, startTransition] = useTransition();

  return (
    <Card>
      <h2 className="mb-1 text-lg font-bold text-slate-900">Logo (optional)</h2>
      <p className="mb-4 text-sm text-slate-500">Shown next to your truck name on the display.</p>
      <div className="flex items-center gap-4">
        {logoUrl ? (
          <Image src={logoUrl} alt="" width={56} height={56} className="h-14 w-14 rounded-full object-cover" />
        ) : (
          <div className="h-14 w-14 rounded-full bg-slate-100" />
        )}
        <form
          action={(formData) => {
            const file = formData.get("logo") as File;
            startTransition(async () => {
              await updateLogo(formData);
              if (file) setLogoUrl(URL.createObjectURL(file));
            });
          }}
          className="flex items-center gap-2"
        >
          <Label htmlFor="logo">
            <span className="sr-only">Logo</span>
          </Label>
          <Input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" className="max-w-xs" />
          <Button type="submit" size="sm" variant="secondary" disabled={pending}>
            {pending ? "Uploading…" : "Upload"}
          </Button>
        </form>
      </div>
    </Card>
  );
}
