(function () {
  "use strict";

  const TURN_SECONDS = 30;

  const state = {
    mode: "adult",
    teamCount: 2,
    teamNames: ["Team 1", "Team 2"],
    scores: [],
    currentTeamIndex: 0,
    targetScore: 30,
    deck: [],
    deckIndex: 0,
    currentWords: [],
    wordStatus: [], // "pending" | "correct" | "skipped"
    turnCorrectCount: 0,
    timeLeft: TURN_SECONDS,
    timerId: null,
    audioCtx: null,
  };

  // ---------- Screen navigation ----------
  function showScreen(id) {
    document.querySelectorAll(".screen").forEach((s) => s.classList.remove("active"));
    document.getElementById(id).classList.add("active");
  }

  document.querySelectorAll(".back-btn").forEach((btn) => {
    btn.addEventListener("click", () => showScreen(btn.dataset.target));
  });

  // ---------- Home screen: mode selection ----------
  document.querySelectorAll(".mode-card").forEach((btn) => {
    btn.addEventListener("click", () => {
      state.mode = btn.dataset.mode;
      document.getElementById("setup-heading").textContent =
        "Set up your game — " + (state.mode === "adult" ? "Adult" : "Junior") + " version";
      renderTeamNameInputs();
      showScreen("screen-setup");
    });
  });

  // ---------- Setup screen: team count ----------
  const teamsCountEl = document.getElementById("teams-count");

  function renderTeamNameInputs() {
    while (state.teamNames.length < state.teamCount) {
      state.teamNames.push("Team " + (state.teamNames.length + 1));
    }
    state.teamNames.length = state.teamCount;

    const wrap = document.getElementById("team-names");
    wrap.innerHTML = "";
    for (let i = 0; i < state.teamCount; i++) {
      const input = document.createElement("input");
      input.type = "text";
      input.className = "team-name-input";
      input.maxLength = 24;
      input.placeholder = "Team " + (i + 1);
      input.value = state.teamNames[i];
      input.addEventListener("input", () => {
        state.teamNames[i] = input.value.trim() || "Team " + (i + 1);
      });
      wrap.appendChild(input);
    }
  }

  document.getElementById("teams-minus").addEventListener("click", () => {
    if (state.teamCount > 2) {
      state.teamCount--;
      teamsCountEl.textContent = state.teamCount;
      renderTeamNameInputs();
    }
  });

  document.getElementById("teams-plus").addEventListener("click", () => {
    if (state.teamCount < 8) {
      state.teamCount++;
      teamsCountEl.textContent = state.teamCount;
      renderTeamNameInputs();
    }
  });

  document.querySelectorAll(".target-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".target-btn").forEach((b) => b.classList.remove("selected"));
      btn.classList.add("selected");
      state.targetScore = parseInt(btn.dataset.targetScore, 10);
    });
  });

  renderTeamNameInputs();

  // ---------- Deck management ----------
  function shuffle(arr) {
    const a = arr.slice();
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  function buildDeck() {
    const source = state.mode === "adult" ? ADULT_WORDS : JUNIOR_WORDS;
    state.deck = shuffle(source);
    state.deckIndex = 0;
  }

  function drawWords(count) {
    const words = [];
    for (let i = 0; i < count; i++) {
      if (state.deckIndex >= state.deck.length) {
        state.deck = shuffle(state.deck);
        state.deckIndex = 0;
      }
      words.push(state.deck[state.deckIndex]);
      state.deckIndex++;
    }
    return words;
  }

  // ---------- Game start ----------
  document.getElementById("start-game-btn").addEventListener("click", () => {
    state.scores = new Array(state.teamCount).fill(0);
    state.currentTeamIndex = 0;
    buildDeck();
    renderScoreboard();
    startTurnSetup();
    showScreen("screen-game");
  });

  // ---------- Scoreboard ----------
  function renderScoreboard() {
    const wrap = document.getElementById("scoreboard");
    wrap.innerHTML = "";
    state.teamNames.forEach((name, i) => {
      const chip = document.createElement("div");
      chip.className = "score-chip" + (i === state.currentTeamIndex ? " active" : "");
      chip.innerHTML = `<span class="score-chip-name">${escapeHtml(name)}</span><span class="score-chip-value">${state.scores[i]}</span>`;
      wrap.appendChild(chip);
    });
  }

  function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }

  // ---------- Turn flow ----------
  const ringFg = document.getElementById("ring-fg");
  const RING_CIRCUMFERENCE = 2 * Math.PI * 54;
  ringFg.style.strokeDasharray = RING_CIRCUMFERENCE;

  function startTurnSetup() {
    document.getElementById("turn-team-name").textContent = state.teamNames[state.currentTeamIndex];
    document.getElementById("pre-turn").classList.remove("hidden");
    document.getElementById("word-panel").classList.add("hidden");
    document.getElementById("post-turn").classList.add("hidden");
    state.timeLeft = TURN_SECONDS;
    updateTimerDisplay();
  }

  document.getElementById("begin-turn-btn").addEventListener("click", () => {
    document.getElementById("pre-turn").classList.add("hidden");
    document.getElementById("word-panel").classList.remove("hidden");
    beginTurn();
  });

  function beginTurn() {
    state.currentWords = drawWords(5);
    state.wordStatus = state.currentWords.map(() => "pending");
    state.turnCorrectCount = 0;
    state.timeLeft = TURN_SECONDS;
    renderWordList();
    updateTimerDisplay();

    clearInterval(state.timerId);
    state.timerId = setInterval(tick, 1000);
  }

  function tick() {
    state.timeLeft--;
    updateTimerDisplay();
    if (state.timeLeft <= 3 && state.timeLeft > 0) playBeep(880, 0.08);
    if (state.timeLeft <= 0) {
      clearInterval(state.timerId);
      playBeep(220, 0.4);
      endTurn();
    }
  }

  function updateTimerDisplay() {
    document.getElementById("timer-number").textContent = Math.max(state.timeLeft, 0);
    const fraction = Math.max(state.timeLeft, 0) / TURN_SECONDS;
    const offset = RING_CIRCUMFERENCE * (1 - fraction);
    ringFg.style.strokeDashoffset = offset;
    ringFg.classList.toggle("urgent", state.timeLeft <= 5);
  }

  function renderWordList() {
    const list = document.getElementById("word-list");
    list.innerHTML = "";
    state.currentWords.forEach((word, idx) => {
      const li = document.createElement("li");
      li.className = "word-item " + state.wordStatus[idx];

      const label = document.createElement("span");
      label.className = "word-label";
      label.textContent = word;
      li.appendChild(label);

      if (state.wordStatus[idx] === "pending") {
        const actions = document.createElement("div");
        actions.className = "word-actions";

        const correctBtn = document.createElement("button");
        correctBtn.className = "word-btn correct-btn";
        correctBtn.textContent = "✓";
        correctBtn.addEventListener("click", () => markWord(idx, "correct"));

        const skipBtn = document.createElement("button");
        skipBtn.className = "word-btn skip-btn";
        skipBtn.textContent = "✗";
        skipBtn.addEventListener("click", () => markWord(idx, "skipped"));

        actions.appendChild(correctBtn);
        actions.appendChild(skipBtn);
        li.appendChild(actions);
      }

      list.appendChild(li);
    });
  }

  function markWord(idx, status) {
    if (state.timeLeft <= 0) return;
    state.wordStatus[idx] = status;
    if (status === "correct") state.turnCorrectCount++;
    renderWordList();

    if (state.wordStatus.every((s) => s !== "pending")) {
      const nextBatch = drawWords(5);
      state.currentWords = state.currentWords.concat(nextBatch);
      state.wordStatus = state.wordStatus.concat(nextBatch.map(() => "pending"));
      renderWordList();
    }
  }

  function endTurn() {
    state.scores[state.currentTeamIndex] += state.turnCorrectCount;
    renderScoreboard();

    document.getElementById("word-panel").classList.add("hidden");
    document.getElementById("turn-score").textContent = state.turnCorrectCount;
    document.getElementById("post-turn").classList.remove("hidden");

    if (state.scores[state.currentTeamIndex] >= state.targetScore) {
      setTimeout(showWinScreen, 400);
    }
  }

  document.getElementById("next-turn-btn").addEventListener("click", () => {
    state.currentTeamIndex = (state.currentTeamIndex + 1) % state.teamCount;
    renderScoreboard();
    startTurnSetup();
  });

  // ---------- Win screen ----------
  function showWinScreen() {
    let winnerIdx = 0;
    for (let i = 1; i < state.scores.length; i++) {
      if (state.scores[i] > state.scores[winnerIdx]) winnerIdx = i;
    }
    document.getElementById("winner-name").textContent = state.teamNames[winnerIdx];

    const finalScores = document.getElementById("final-scores");
    finalScores.innerHTML = "";
    state.teamNames
      .map((name, i) => ({ name, score: state.scores[i] }))
      .sort((a, b) => b.score - a.score)
      .forEach((t) => {
        const row = document.createElement("div");
        row.className = "final-score-row";
        row.innerHTML = `<span>${escapeHtml(t.name)}</span><span>${t.score}</span>`;
        finalScores.appendChild(row);
      });

    showScreen("screen-win");
  }

  document.getElementById("play-again-btn").addEventListener("click", () => {
    showScreen("screen-home");
  });

  // ---------- Audio (no external assets — simple beep via Web Audio API) ----------
  function playBeep(freq, duration) {
    try {
      if (!state.audioCtx) {
        state.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      const ctx = state.audioCtx;
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.frequency.value = freq;
      osc.type = "sine";
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + duration);
    } catch (e) {
      // Audio not available — fail silently.
    }
  }
})();
