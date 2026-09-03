"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import { DisplayData } from "@/lib/display-data";
import { THEME_CLASSES, ThemeName } from "@/lib/themes";
import { formatPrice, cn } from "@/lib/utils";

const POLL_INTERVAL_MS = 20_000;
const CACHE_KEY_PREFIX = "menuscreen:display:";

export function DisplayView({
  slug,
  initialData,
}: {
  slug: string;
  initialData: DisplayData;
}) {
  const cacheKey = `${CACHE_KEY_PREFIX}${slug}`;

  // On first render in the browser, prefer a locally cached copy over the
  // server-rendered one if it happens to be newer (e.g. the page was served
  // from an edge/browser cache during a brief outage). Reading this lazily
  // in the initializer — rather than in an effect after mount — avoids an
  // extra render and a flash of stale content.
  const [data, setData] = useState<DisplayData>(() => {
    if (typeof window === "undefined") return initialData;
    try {
      const cached = localStorage.getItem(cacheKey);
      if (cached) {
        const parsed = JSON.parse(cached) as DisplayData;
        if (parsed.updatedAt > initialData.updatedAt) return parsed;
      }
    } catch {
      // Ignore corrupt/unavailable cache.
    }
    return initialData;
  });
  const [connected, setConnected] = useState(true);

  // Cache the freshest known-good menu locally so a reload during a brief
  // network hiccup can fall back to "last loaded" instead of showing nothing.
  useEffect(() => {
    try {
      localStorage.setItem(cacheKey, JSON.stringify(data));
    } catch {
      // Storage can be unavailable (private mode, quota) — safe to ignore.
    }
  }, [data, cacheKey]);

  useEffect(() => {
    let cancelled = false;

    async function poll() {
      try {
        const res = await fetch(`/api/display/${slug}`, { cache: "no-store" });
        if (!res.ok) throw new Error("bad response");
        const fresh = (await res.json()) as DisplayData;
        if (!cancelled) {
          setData(fresh);
          setConnected(true);
        }
      } catch {
        // Keep showing the last-loaded menu instead of clearing it.
        if (!cancelled) setConnected(false);
      }
    }

    const id = setInterval(poll, POLL_INTERVAL_MS);
    return () => {
      cancelled = true;
      clearInterval(id);
    };
  }, [slug]);

  const theme = THEME_CLASSES[data.theme as ThemeName] ?? THEME_CLASSES.NEON;
  const isPortrait = data.orientation === "PORTRAIT";

  return (
    <div className={cn("min-h-screen w-full overflow-hidden", theme.page)}>
      <div className="mx-auto flex h-screen max-w-[1800px] flex-col px-8 py-6 sm:px-12">
        <header className="mb-4 flex items-center gap-4">
          {data.logoUrl && (
            <Image
              src={data.logoUrl}
              alt=""
              width={72}
              height={72}
              className="h-16 w-16 rounded-full object-cover sm:h-[72px] sm:w-[72px]"
            />
          )}
          <h1 className={cn("text-4xl font-black tracking-tight sm:text-5xl", theme.truckName)}>
            {data.name}
          </h1>
          <div
            className={cn(
              "ml-auto h-3 w-3 shrink-0 rounded-full transition-colors",
              connected ? "bg-emerald-400" : "bg-amber-400 animate-pulse"
            )}
            title={connected ? "Live" : "Reconnecting… showing last known menu"}
          />
        </header>

        {data.specialActive && data.specialText && (
          <div
            className={cn(
              "mb-5 rounded-2xl px-6 py-4 text-2xl font-bold sm:text-3xl",
              theme.special
            )}
          >
            ⭐ Today&apos;s Special: {data.specialText}
          </div>
        )}

        <div
          className={cn(
            "min-h-0 flex-1 gap-8 overflow-y-auto",
            isPortrait ? "flex flex-col" : "grid auto-cols-[minmax(320px,1fr)] grid-flow-col"
          )}
        >
          {data.categories.map((category) => (
            <section key={category.id} className="min-w-0">
              <h2 className={cn("mb-3 pb-2 text-2xl font-bold uppercase tracking-wide sm:text-3xl", theme.categoryTitle)}>
                {category.name}
              </h2>
              <div className="space-y-3">
                {category.items.map((item) => (
                  <div
                    key={item.id}
                    className={cn(
                      "relative flex items-center gap-4 rounded-xl p-4",
                      theme.card,
                      item.soldOut && theme.soldOutCard
                    )}
                  >
                    {item.photoUrl && (
                      <Image
                        src={item.photoUrl}
                        alt=""
                        width={64}
                        height={64}
                        className="h-16 w-16 shrink-0 rounded-lg object-cover"
                      />
                    )}
                    <div className="min-w-0 flex-1">
                      <div className="flex items-baseline gap-2">
                        <span className={cn("text-xl font-bold sm:text-2xl", theme.itemName)}>
                          {item.name}
                        </span>
                        {item.soldOut && (
                          <span
                            className={cn(
                              "rounded px-2 py-0.5 text-xs font-bold tracking-wide",
                              theme.soldOutBadge
                            )}
                          >
                            SOLD OUT
                          </span>
                        )}
                      </div>
                      {item.description && (
                        <p className={cn("mt-0.5 text-base", theme.itemDesc)}>{item.description}</p>
                      )}
                    </div>
                    <span className={cn("shrink-0 text-xl font-bold sm:text-2xl", theme.price)}>
                      {formatPrice(item.price)}
                    </span>
                  </div>
                ))}
                {category.items.length === 0 && (
                  <p className={cn("text-base italic opacity-60", theme.itemDesc)}>
                    Nothing in this category yet.
                  </p>
                )}
              </div>
            </section>
          ))}
        </div>
      </div>
    </div>
  );
}
