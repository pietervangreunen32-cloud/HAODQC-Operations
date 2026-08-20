import type { NextAuthConfig } from "next-auth";

// Edge-safe config (no Prisma/bcrypt here) shared between the full auth
// setup (src/auth.ts) and the middleware, which runs on the Edge runtime.
export const authConfig = {
  pages: {
    signIn: "/login",
  },
  session: { strategy: "jwt" },
  providers: [],
  callbacks: {
    authorized({ auth, request }) {
      const isLoggedIn = !!auth?.user;
      const isAdminRoute = request.nextUrl.pathname.startsWith("/admin");
      if (isAdminRoute) return isLoggedIn;
      return true;
    },
  },
} satisfies NextAuthConfig;
