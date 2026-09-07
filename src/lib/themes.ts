export const THEMES = ["NEON", "CHALKBOARD", "MINIMALIST", "COLORFUL"] as const;
export type ThemeName = (typeof THEMES)[number];

// CUSTOM is a separate, Fleet-plan theme: its colors come from the
// business's own customPrimaryColor/customBackgroundColor/etc. fields
// (applied as inline styles in DisplayView) rather than a fixed class set
// here, so it isn't part of THEMES/THEME_META (the pickable preset list).
export type ThemeNameOrCustom = ThemeName | "CUSTOM";

// A curated, small list rather than free-text font entry — keeps the
// display fast (only the fonts actually offered ever get loaded) and
// avoids arbitrary text ending up in a Google Fonts URL.
export const CUSTOM_FONTS = [
  { value: "poppins", label: "Poppins (clean & modern)", family: "Poppins:wght@400;700;900" },
  { value: "inter", label: "Inter (neutral & readable)", family: "Inter:wght@400;700;900" },
  { value: "playfair", label: "Playfair Display (elegant serif)", family: "Playfair+Display:wght@700;900" },
  { value: "bebas", label: "Bebas Neue (bold & condensed)", family: "Bebas+Neue" },
  { value: "caveat", label: "Caveat (handwritten)", family: "Caveat:wght@600;700" },
  { value: "oswald", label: "Oswald (punchy & condensed)", family: "Oswald:wght@400;700" },
] as const;
export type CustomFontValue = (typeof CUSTOM_FONTS)[number]["value"];

export function customFontFamily(value: string | null | undefined) {
  const found = CUSTOM_FONTS.find((f) => f.value === value);
  return found ? found.family : CUSTOM_FONTS[0].family;
}

export function customFontCss(value: string | null | undefined) {
  const found = CUSTOM_FONTS.find((f) => f.value === value) ?? CUSTOM_FONTS[0];
  // The part before ":" in the Google Fonts family string is the actual
  // CSS font-family name (spaces restored from the URL's "+" separator).
  return found.family.split(":")[0].replace(/\+/g, " ");
}

const HEX_COLOR_RE = /^#[0-9a-fA-F]{6}$/;
export function isValidHexColor(value: string): boolean {
  return HEX_COLOR_RE.test(value);
}

export const THEME_META: Record<ThemeName, { label: string; blurb: string; swatch: string }> = {
  NEON: {
    label: "Dark Neon",
    blurb: "Black background with glowing pink & cyan accents. Bold and eye-catching after dark.",
    swatch: "linear-gradient(135deg,#0a0a12,#ff2d95,#00e5ff)",
  },
  CHALKBOARD: {
    label: "Chalkboard",
    blurb: "Classic hand-written chalk look on a dark green board. Cozy street-food feel.",
    swatch: "linear-gradient(135deg,#1f2a24,#f5f0e6)",
  },
  MINIMALIST: {
    label: "Minimalist",
    blurb: "Clean white background, crisp black text. Easy to read in bright daylight.",
    swatch: "linear-gradient(135deg,#ffffff,#111827)",
  },
  COLORFUL: {
    label: "Colorful",
    blurb: "Bright, playful gradient background. Fun and energetic for a casual crowd.",
    swatch: "linear-gradient(135deg,#ff7a18,#af002d,#319197)",
  },
};

export const THEME_CLASSES: Record<
  ThemeName,
  {
    page: string;
    heading: string;
    businessName: string;
    categoryTitle: string;
    card: string;
    itemName: string;
    itemDesc: string;
    price: string;
    soldOutCard: string;
    soldOutBadge: string;
    special: string;
  }
> = {
  NEON: {
    page: "bg-black text-white",
    heading: "text-fuchsia-400",
    businessName: "text-white drop-shadow-[0_0_18px_rgba(255,45,149,0.8)]",
    categoryTitle:
      "text-cyan-300 border-b-2 border-cyan-400/60 drop-shadow-[0_0_10px_rgba(34,211,238,0.6)]",
    card: "bg-white/5 border border-fuchsia-500/30 backdrop-blur",
    itemName: "text-white",
    itemDesc: "text-fuchsia-100/70",
    price: "text-cyan-300",
    soldOutCard: "opacity-40 grayscale",
    soldOutBadge: "bg-fuchsia-600 text-white",
    special: "bg-fuchsia-600/90 text-white shadow-[0_0_30px_rgba(255,45,149,0.6)]",
  },
  CHALKBOARD: {
    page: "bg-[#1f2a24] text-[#f5f0e6]",
    heading: "text-[#f5f0e6]",
    businessName: "text-[#f5f0e6]",
    categoryTitle: "text-amber-200 border-b-2 border-dashed border-amber-200/50",
    card: "bg-white/5 border border-white/10",
    itemName: "text-[#f5f0e6]",
    itemDesc: "text-[#f5f0e6]/60",
    price: "text-amber-200",
    soldOutCard: "opacity-40",
    soldOutBadge: "bg-red-500/80 text-white",
    special: "bg-amber-200/90 text-[#1f2a24]",
  },
  MINIMALIST: {
    page: "bg-white text-slate-900",
    heading: "text-slate-900",
    businessName: "text-slate-900",
    categoryTitle: "text-slate-900 border-b-2 border-slate-900",
    card: "bg-slate-50 border border-slate-200",
    itemName: "text-slate-900",
    itemDesc: "text-slate-500",
    price: "text-slate-900",
    soldOutCard: "opacity-40",
    soldOutBadge: "bg-slate-900 text-white",
    special: "bg-slate-900 text-white",
  },
  COLORFUL: {
    page: "bg-gradient-to-br from-orange-500 via-rose-600 to-teal-600 text-white",
    heading: "text-white",
    businessName: "text-white drop-shadow-lg",
    categoryTitle: "text-white border-b-2 border-white/70",
    card: "bg-white/15 border border-white/30 backdrop-blur",
    itemName: "text-white",
    itemDesc: "text-white/80",
    price: "text-yellow-200",
    soldOutCard: "opacity-40 grayscale",
    soldOutBadge: "bg-black/60 text-white",
    special: "bg-yellow-300 text-slate-900",
  },
};
