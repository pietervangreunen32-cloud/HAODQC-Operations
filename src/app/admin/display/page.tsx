import QRCode from "qrcode";
import Link from "next/link";
import { requireTruck } from "@/lib/current-truck";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { CopyLinkButton } from "@/components/admin/copy-link-button";

export default async function DisplayLinkPage() {
  const { truck } = await requireTruck();

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const displayUrl = `${appUrl}/display/${truck.slug}`;
  const qrDataUrl = await QRCode.toDataURL(displayUrl, {
    width: 320,
    margin: 1,
    color: { dark: "#0f172a", light: "#ffffff" },
  });

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Display link &amp; QR code</h1>
        <p className="text-sm text-slate-500">
          Open this link on the screen you want your menu displayed on.
        </p>
      </div>

      <Card className="flex flex-col items-center gap-4 text-center sm:flex-row sm:text-left">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src={qrDataUrl} alt="QR code linking to your display" width={200} height={200} />
        <div className="flex-1">
          <p className="mb-1 text-sm font-medium text-slate-500">Your display link</p>
          <p className="mb-4 break-all rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm text-slate-800">
            {displayUrl}
          </p>
          <div className="flex flex-wrap gap-2">
            <CopyLinkButton url={displayUrl} />
            <Link href={`/display/${truck.slug}`} target="_blank">
              <Button variant="secondary">Open display ↗</Button>
            </Link>
          </div>
        </div>
      </Card>

      <Card>
        <h2 className="mb-2 text-lg font-bold text-slate-900">How to put it on your TV</h2>
        <p className="text-sm text-slate-600">
          Scan the QR code with your phone, or type the link into any TV browser, Fire
          Stick, or Android box.
        </p>
        <Link href="/admin/help" className="mt-3 inline-block text-sm font-medium text-orange-600 hover:underline">
          Full step-by-step instructions →
        </Link>
      </Card>
    </div>
  );
}
