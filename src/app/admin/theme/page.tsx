import { requireBusiness } from "@/lib/current-business";
import { ThemePicker } from "@/components/admin/theme-picker";
import { LogoUploader } from "@/components/admin/logo-uploader";
import { CustomThemeEditor } from "@/components/admin/custom-theme-editor";
import { FunctionalityToggles } from "@/components/admin/functionality-toggles";

export default async function ThemePage() {
  const { business } = await requireBusiness();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Theme, branding &amp; settings</h1>
        <p className="text-sm text-slate-500">No design skill required — just pick one.</p>
      </div>
      <ThemePicker initialTheme={business.theme} initialOrientation={business.orientation} />
      <LogoUploader initialLogoUrl={business.logoUrl} />
      <CustomThemeEditor
        isActive={business.theme === "CUSTOM"}
        initialPrimaryColor={business.customPrimaryColor || "#ea580c"}
        initialBackgroundColor={business.customBackgroundColor || "#0f172a"}
        initialTextColor={business.customTextColor || "#ffffff"}
        initialFont={business.customFont || "poppins"}
      />
      <FunctionalityToggles initialHideSoldOut={business.hideSoldOutItems} />
    </div>
  );
}
