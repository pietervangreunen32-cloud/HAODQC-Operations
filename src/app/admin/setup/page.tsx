import QRCode from "qrcode";
import { requireTruckWithMenu } from "@/lib/current-truck";
import { SetupWizard } from "@/components/admin/setup-wizard";

export default async function SetupPage() {
  const { truck } = await requireTruckWithMenu();

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const displayUrl = `${appUrl}/display/${truck.slug}`;
  const qrDataUrl = await QRCode.toDataURL(displayUrl, {
    width: 240,
    margin: 1,
    color: { dark: "#0f172a", light: "#ffffff" },
  });

  return (
    <div className="mx-auto max-w-3xl">
      <SetupWizard
        categories={truck.categories}
        theme={truck.theme}
        orientation={truck.orientation}
        displayUrl={displayUrl}
        qrDataUrl={qrDataUrl}
      />
    </div>
  );
}
