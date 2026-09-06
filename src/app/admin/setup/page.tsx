import QRCode from "qrcode";
import { requireBusinessWithMenu } from "@/lib/current-business";
import { SetupWizard } from "@/components/admin/setup-wizard";

export default async function SetupPage() {
  const { business } = await requireBusinessWithMenu();

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const displayUrl = `${appUrl}/display/${business.slug}`;
  const qrDataUrl = await QRCode.toDataURL(displayUrl, {
    width: 240,
    margin: 1,
    color: { dark: "#0f172a", light: "#ffffff" },
  });

  return (
    <div className="mx-auto max-w-3xl">
      <SetupWizard
        categories={business.categories}
        theme={business.theme}
        orientation={business.orientation}
        displayUrl={displayUrl}
        qrDataUrl={qrDataUrl}
      />
    </div>
  );
}
