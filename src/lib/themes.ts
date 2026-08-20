export const THEMES = ["NEON", "CHALKBOARD", "MINIMALIST", "COLORFUL"] as const;
export type ThemeName = (typeof THEMES)[number];

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
    truckName: string;
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
    truckName: "text-white drop-shadow-[0_0_18px_rgba(255,45,149,0.8)]",
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
    truckName: "text-[#f5f0e6]",
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
    truckName: "text-slate-900",
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
    truckName: "text-white drop-shadow-lg",
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
