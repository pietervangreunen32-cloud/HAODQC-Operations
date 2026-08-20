import { Card } from "@/components/ui/card";

const OPTIONS = [
  {
    title: "Amazon Fire Stick / Fire TV",
    steps: [
      'Plug the Fire Stick into your TV and turn it on.',
      'From the home screen, search for and install a browser app — "Silk Browser" is usually already installed, or search the app store for "Firefox".',
      "Open the browser and type in your display link (or use the on-screen keyboard).",
      'Once it loads, most browsers have a "Full Screen" option in their menu — turn that on so no browser bars show.',
      "Leave it open. It will keep updating on its own.",
    ],
  },
  {
    title: "An old Android phone or tablet",
    steps: [
      "Any old Android phone or tablet works great — it doesn't need a SIM card, just Wi-Fi.",
      "Open Chrome (or any browser) and type in your display link.",
      'Tap the menu (⋮) and look for "Add to Home Screen" — this creates an app icon that opens straight to full screen.',
      "Prop the phone/tablet up facing outward on your truck (a cheap phone mount or stand works well).",
      "Turn off auto-lock / screen timeout in the phone's Settings so it doesn't go to sleep (Settings → Display → Screen timeout → set to \"Never\").",
    ],
  },
  {
    title: "A basic Android TV box",
    steps: [
      "Connect the box to your TV and Wi-Fi.",
      "Open the pre-installed browser, or install Chrome/Firefox from the app store.",
      "Type in your display link and open it.",
      "Use the browser's full-screen option if it has one.",
    ],
  },
  {
    title: "A smart TV's built-in browser",
    steps: [
      "Many smart TVs have a browser built in under Apps.",
      "Open it and type in your display link.",
      "Smart TV browsers vary — if yours looks cramped or slow, a $30 Fire Stick or old tablet will usually give a smoother result.",
    ],
  },
];

export default function HelpPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">How to put this on your TV</h1>
        <p className="text-sm text-slate-500">
          You don&apos;t need to install an app. You just need a screen with a web browser.
        </p>
      </div>

      <Card className="bg-orange-50 border-orange-200">
        <p className="text-sm text-orange-900">
          <strong>The short version:</strong> get any screen with a web browser, open your
          display link from the &quot;Display &amp; QR&quot; page, and leave it open. It updates
          on its own — you never have to touch the screen again after that.
        </p>
      </Card>

      <div className="grid gap-4 sm:grid-cols-2">
        {OPTIONS.map((option) => (
          <Card key={option.title}>
            <h2 className="mb-3 font-bold text-slate-900">{option.title}</h2>
            <ol className="list-decimal space-y-2 pl-4 text-sm text-slate-600">
              {option.steps.map((step, i) => (
                <li key={i}>{step}</li>
              ))}
            </ol>
          </Card>
        ))}
      </div>

      <Card>
        <h2 className="mb-2 font-bold text-slate-900">Tips</h2>
        <ul className="list-disc space-y-1.5 pl-4 text-sm text-slate-600">
          <li>Make sure the device is on Wi-Fi with a signal reachable at your truck.</li>
          <li>If the screen briefly loses internet, it will keep showing your last saved menu instead of going blank.</li>
          <li>You can update your menu from your phone any time — changes appear on the screen within seconds.</li>
          <li>If a device&apos;s screen turns off automatically, look for a &quot;screen timeout&quot; or &quot;sleep&quot; setting and set it to never (or plug into &quot;always on&quot; power settings if available).</li>
        </ul>
      </Card>
    </div>
  );
}
