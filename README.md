# 30 Seconds — Online Word Game

A browser-based version of the fast-talking party game "30 Seconds", with **Adult** and **Junior** word decks and a live 30-second timer.

## How to play

1. Open `index.html` in a browser (or host the folder on any static site, e.g. GitHub Pages).
2. Pick the **Adult** or **Junior** version.
3. Set up your teams and choose a winning score (20/30/40/50).
4. Each turn, the active team's describer explains 5 words to their teammates without saying the word itself. Tap **✓** for every word guessed correctly and **✗** to skip a word — the timer runs for 30 seconds per turn.
5. The team's score for the turn is added to their total. Play continues, team by team, until someone reaches the winning score.

## Playing online

This is a static site (plain HTML/CSS/JS, no build step or server required). To make it playable online for others, host the three files (`index.html`, `style.css`, `app.js`, `words.js`) on any static hosting provider, for example:

- **GitHub Pages**: enable Pages on this repository (Settings → Pages → deploy from the branch/folder containing these files).
- Netlify, Vercel, or any static file host: drag-and-drop the folder or connect the repo.

No backend, database, or API keys are needed — everything (word decks, timer, scoring) runs client-side in the browser.

## Files

- `index.html` — app markup and screens (home, setup, game, win).
- `style.css` — styling and timer animation.
- `app.js` — game logic: team setup, turn timer, scoring, word deck cycling.
- `words.js` — the Adult and Junior word lists.

## Customizing the word lists

Edit the `ADULT_WORDS` and `JUNIOR_WORDS` arrays in `words.js` to add, remove, or localize words.
