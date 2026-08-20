import { requireTruck } from "@/lib/current-truck";
import { ThemePicker } from "@/components/admin/theme-picker";
import { LogoUploader } from "@/components/admin/logo-uploader";

export default async function ThemePage() {
  const { truck } = await requireTruck();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Theme &amp; look</h1>
        <p className="text-sm text-slate-500">No design skill required — just pick one.</p>
      </div>
      <ThemePicker initialTheme={truck.theme} initialOrientation={truck.orientation} />
      <LogoUploader initialLogoUrl={truck.logoUrl} />
    </div>
  );
}
