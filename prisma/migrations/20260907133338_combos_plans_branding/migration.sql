-- CreateTable
CREATE TABLE "Combo" (
    "id" TEXT NOT NULL PRIMARY KEY,
    "businessId" TEXT NOT NULL,
    "name" TEXT NOT NULL,
    "description" TEXT,
    "price" REAL NOT NULL,
    "active" BOOLEAN NOT NULL DEFAULT true,
    "order" INTEGER NOT NULL DEFAULT 0,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" DATETIME NOT NULL,
    CONSTRAINT "Combo_businessId_fkey" FOREIGN KEY ("businessId") REFERENCES "Business" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

-- RedefineTables
PRAGMA defer_foreign_keys=ON;
PRAGMA foreign_keys=OFF;
CREATE TABLE "new_Business" (
    "id" TEXT NOT NULL PRIMARY KEY,
    "ownerId" TEXT NOT NULL,
    "name" TEXT NOT NULL,
    "slug" TEXT NOT NULL,
    "theme" TEXT NOT NULL DEFAULT 'NEON',
    "orientation" TEXT NOT NULL DEFAULT 'LANDSCAPE',
    "logoUrl" TEXT,
    "specialText" TEXT,
    "specialActive" BOOLEAN NOT NULL DEFAULT false,
    "onboardedAt" DATETIME,
    "viewCount" INTEGER NOT NULL DEFAULT 0,
    "plan" TEXT NOT NULL DEFAULT 'SAMPLER',
    "showCombosOnDisplay" BOOLEAN NOT NULL DEFAULT true,
    "customPrimaryColor" TEXT,
    "customBackgroundColor" TEXT,
    "customTextColor" TEXT,
    "customFont" TEXT,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" DATETIME NOT NULL,
    CONSTRAINT "Business_ownerId_fkey" FOREIGN KEY ("ownerId") REFERENCES "User" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);
INSERT INTO "new_Business" ("createdAt", "id", "logoUrl", "name", "onboardedAt", "orientation", "ownerId", "slug", "specialActive", "specialText", "theme", "updatedAt", "viewCount") SELECT "createdAt", "id", "logoUrl", "name", "onboardedAt", "orientation", "ownerId", "slug", "specialActive", "specialText", "theme", "updatedAt", "viewCount" FROM "Business";
DROP TABLE "Business";
ALTER TABLE "new_Business" RENAME TO "Business";
CREATE UNIQUE INDEX "Business_slug_key" ON "Business"("slug");
PRAGMA foreign_keys=ON;
PRAGMA defer_foreign_keys=OFF;
