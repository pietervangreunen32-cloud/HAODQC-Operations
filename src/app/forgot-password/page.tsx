"use client";

import { useActionState } from "react";
import Link from "next/link";
import { forgotPasswordAction } from "@/lib/actions/auth";
import { Button } from "@/components/ui/button";
import { Input, Label } from "@/components/ui/input";
import { Card } from "@/components/ui/card";

export default function ForgotPasswordPage() {
  const [state, formAction, pending] = useActionState(forgotPasswordAction, undefined);

  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12">
      <Card className="w-full max-w-md">
        <h1 className="text-2xl font-bold text-slate-900">Reset your password</h1>
        <p className="mt-1 text-sm text-slate-500">
          Enter your account email and we&apos;ll generate a reset link.
        </p>

        <form action={formAction} className="mt-6 space-y-4">
          <div>
            <Label htmlFor="email">Email</Label>
            <Input id="email" name="email" type="email" required placeholder="you@example.com" />
          </div>

          {state?.error && (
            <p className="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
              {state.error}
            </p>
          )}
          {state?.resetUrl && (
            <div className="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
              <p>Reset link generated:</p>
              <Link href={state.resetUrl} className="font-medium underline break-all">
                {state.resetUrl}
              </Link>
              <p className="mt-1 text-xs text-emerald-700">
                (In production this would be emailed to you automatically.)
              </p>
            </div>
          )}

          <Button type="submit" className="w-full" size="lg" disabled={pending}>
            {pending ? "Working…" : "Send reset link"}
          </Button>
        </form>

        <p className="mt-6 text-center text-sm text-slate-500">
          <Link href="/login" className="font-medium text-orange-600 hover:underline">
            Back to login
          </Link>
        </p>
      </Card>
    </main>
  );
}
