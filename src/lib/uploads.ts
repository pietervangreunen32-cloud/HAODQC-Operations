import { mkdir, writeFile } from "fs/promises";
import path from "path";
import crypto from "crypto";

const ALLOWED_TYPES: Record<string, string> = {
  "image/png": "png",
  "image/jpeg": "jpg",
  "image/webp": "webp",
};
const MAX_BYTES = 5 * 1024 * 1024; // 5MB

// Saves an uploaded image to /public/uploads and returns its public URL.
//
// This writes to the local filesystem, which is fine for local dev or a
// traditional always-on server, but on serverless hosts like Vercel the
// filesystem is read-only/ephemeral outside /tmp — uploaded files would
// disappear on the next deploy. Before going live on Vercel, swap this for
// Vercel Blob or Supabase Storage (both are a few lines of SDK code in
// place of writeFile below); everywhere else in the app just uses the
// returned URL string, so nothing else needs to change.
export async function saveUpload(
  file: File,
  scopeId: string
): Promise<string | null> {
  if (!file || file.size === 0) return null;
  if (file.size > MAX_BYTES) {
    throw new Error("Image is too large (max 5MB).");
  }
  const ext = ALLOWED_TYPES[file.type];
  if (!ext) {
    throw new Error("Please upload a PNG, JPEG, or WebP image.");
  }

  const dir = path.join(process.cwd(), "public", "uploads", scopeId);
  await mkdir(dir, { recursive: true });

  const filename = `${crypto.randomUUID()}.${ext}`;
  const bytes = Buffer.from(await file.arrayBuffer());
  await writeFile(path.join(dir, filename), bytes);

  return `/uploads/${scopeId}/${filename}`;
}
