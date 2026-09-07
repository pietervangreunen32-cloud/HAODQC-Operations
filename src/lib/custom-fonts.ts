import { Poppins, Inter, Playfair_Display, Bebas_Neue, Caveat, Oswald } from "next/font/google";
import type { CustomFontValue } from "@/lib/themes";

// Self-hosted via next/font (no runtime request to Google Fonts, no
// layout shift) — one of these six is picked per-business based on their
// saved `customFont` value. Each must be called at module scope with
// literal arguments for Next's build-time font optimization to work.
const poppins = Poppins({ subsets: ["latin"], weight: ["400", "700", "900"], display: "swap" });
const inter = Inter({ subsets: ["latin"], weight: ["400", "700", "900"], display: "swap" });
const playfair = Playfair_Display({ subsets: ["latin"], weight: ["700", "900"], display: "swap" });
const bebas = Bebas_Neue({ subsets: ["latin"], weight: ["400"], display: "swap" });
const caveat = Caveat({ subsets: ["latin"], weight: ["600", "700"], display: "swap" });
const oswald = Oswald({ subsets: ["latin"], weight: ["400", "700"], display: "swap" });

export const CUSTOM_FONT_CLASSNAMES: Record<CustomFontValue, string> = {
  poppins: poppins.className,
  inter: inter.className,
  playfair: playfair.className,
  bebas: bebas.className,
  caveat: caveat.className,
  oswald: oswald.className,
};

export function customFontClassName(value: string | null | undefined): string {
  return CUSTOM_FONT_CLASSNAMES[(value as CustomFontValue) ?? "poppins"] ?? poppins.className;
}
