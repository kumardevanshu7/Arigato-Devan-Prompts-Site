document.addEventListener("DOMContentLoaded", () => {
  const cards = document.querySelectorAll(".card");

  // ── ✨ NEW Sparkle Badge — for prompts created in last 48 hours ──
  (function markNewCards() {
    const FORTY_EIGHT_HOURS = 48 * 60 * 60 * 1000;
    const now = Date.now();

    if (!document.getElementById("new-badge-styles")) {
      const st = document.createElement("style");
      st.id = "new-badge-styles";
      st.textContent =
        "@keyframes newBadgePulse{0%,100%{transform:scale(1) rotate(-8deg)}50%{transform:scale(1.1) rotate(-8deg)}}" +
        "@keyframes newBadgeSparkle{0%,100%{opacity:1;transform:rotate(0)}50%{opacity:.6;transform:rotate(20deg)}}" +
        ".new-spark-badge{position:absolute;top:10px;left:10px;z-index:10;display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:linear-gradient(135deg,#ff6b9d,#fb923c);color:#fff;font-family:var(--font-main,'Outfit',sans-serif);font-weight:900;font-size:.7rem;letter-spacing:.5px;border:2px solid var(--text-color,#2d2a35);border-radius:999px;box-shadow:2px 2px 0 var(--text-color,#2d2a35);text-transform:uppercase;pointer-events:none;animation:newBadgePulse 1.5s ease-in-out infinite;}" +
        ".new-spark-badge .spark{display:inline-block;animation:newBadgeSparkle 1s ease-in-out infinite}";
      document.head.appendChild(st);
    }

    document.querySelectorAll(".card[data-created]").forEach((card) => {
      const created = card.dataset.created;
      if (!created) return;
      // MySQL "YYYY-MM-DD HH:MM:SS" → ISO-ish so JS can parse
      const ts = new Date(created.replace(" ", "T")).getTime();
      if (isNaN(ts)) return;
      if (now - ts > FORTY_EIGHT_HOURS) return;
      if (card.querySelector(".new-spark-badge")) return;

      // Ensure card has positioned context for absolute badge
      if (window.getComputedStyle(card).position === "static") {
        card.style.position = "relative";
      }

      const badge = document.createElement("div");
      badge.className = "new-spark-badge";
      badge.innerHTML = '<span class="spark">✨</span><span>NEW</span>';
      card.appendChild(badge);
    });
  })();

  const modalOverlay = document.getElementById("unlock-modal");
  const closeModalBtn = document.querySelector(".close-modal");
  const submitCodeBtn = document.getElementById("submit-code");
  const codeInput = document.getElementById("unlock-code-input");

  let currentPromptId = null;
  let currentCardElement = null;

  // Custom Alert System — styled toast notification
  function showComicAlert(message, type = "error") {
    const toast = document.createElement("div");
    toast.textContent = message;
    Object.assign(toast.style, {
      position: "fixed",
      bottom: "32px",
      left: "50%",
      transform: "translateX(-50%)",
      background: type === "error" ? "#fff1b8" : "#fdfbf7",
      color: "#2d2a35",
      border: "3px solid #2d2a35",
      borderRadius: "14px",
      boxShadow: "4px 4px 0px #2d2a35",
      fontFamily: "Outfit, sans-serif",
      fontWeight: "800",
      fontSize: "1rem",
      padding: "14px 28px",
      zIndex: "99999",
      opacity: "1",
      transition: "opacity 0.4s ease",
      pointerEvents: "none",
      whiteSpace: "nowrap",
      maxWidth: "90vw",
      textAlign: "center",
    });
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = "0";
      setTimeout(() => toast.remove(), 420);
    }, 2500);
  }

  // Apply visual SAVE / SAVED state to the modal save button.
  // When `isSaved` is true the button is rendered as the disabled "SAVED" pill,
  // signalling the user can only unsave from the Saved Prompts page.
  function applySaveBtnState(saveBtn, isSaved) {
    if (!saveBtn) return;
    if (isSaved) {
      saveBtn.classList.add("save-btn-saved");
      saveBtn.disabled = true;
      saveBtn.dataset.saved = "true";
      saveBtn.title = "Manage from Saved Prompts page";
      saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> SAVED';
    } else {
      saveBtn.classList.remove("save-btn-saved");
      saveBtn.disabled = false;
      saveBtn.dataset.saved = "false";
      saveBtn.title = "";
      saveBtn.innerHTML = '<i class="fa-solid fa-bookmark"></i> SAVE';
      saveBtn.style.background = "";
      saveBtn.style.color = "";
    }
  }
  window.applySaveBtnState = applySaveBtnState;

  // --- Swipe Stack Logic ---
  const cardStack = document.getElementById("card-stack");
  const prevBtn = document.getElementById("swipe-left-btn");
  const nextBtn = document.getElementById("swipe-right-btn");

  let allCards = document.querySelectorAll(".card");
  let currentIndex = 0;
  window.isSwiping = false;

  // Only apply stack logic if this is a card-stack swiper page (not gallery grid)
  const isGalleryGrid = document.body.classList.contains("page-gallery");

  function updateCardStack() {
    if (isGalleryGrid) return; // Gallery uses grid, not stack
    allCards.forEach((card, index) => {
      card.classList.remove("card-active", "card-next", "card-prev");
      if (index === currentIndex) {
        card.classList.add("card-active");
      } else if (index > currentIndex) {
        card.classList.add("card-next");
      } else {
        card.classList.add("card-prev");
      }
    });
  }

  function swipeNext() {
    if (window.innerWidth > 900) return;
    if (currentIndex < allCards.length - 1) {
      currentIndex++;
      updateCardStack();
    }
  }

  function swipePrev() {
    if (window.innerWidth > 900) return;
    if (currentIndex > 0) {
      currentIndex--;
      updateCardStack();
    }
  }

  if (prevBtn) prevBtn.addEventListener("click", swipePrev);
  if (nextBtn) nextBtn.addEventListener("click", swipeNext);

  if (cardStack && !isGalleryGrid) {
    let startX = 0;
    let isDragging = false;

    cardStack.addEventListener(
      "touchstart",
      (e) => {
        if (window.innerWidth > 900) return;
        startX = e.touches[0].clientX;
        isDragging = true;
        window.isSwiping = false;
      },
      { passive: true },
    );

    cardStack.addEventListener(
      "touchmove",
      (e) => {
        if (!isDragging) return;
        if (Math.abs(e.touches[0].clientX - startX) > 10)
          window.isSwiping = true;
      },
      { passive: true },
    );

    cardStack.addEventListener("touchend", (e) => {
      if (!isDragging) return;
      let endX = e.changedTouches[0].clientX;
      handleSwipe(startX, endX);
      isDragging = false;
      setTimeout(() => (window.isSwiping = false), 50);
    });

    cardStack.addEventListener("mousedown", (e) => {
      if (window.innerWidth > 900) return;
      if (e.target.closest(".like-btn")) return;
      startX = e.clientX;
      isDragging = true;
      window.isSwiping = false;
    });

    cardStack.addEventListener("mousemove", (e) => {
      if (!isDragging) return;
      if (Math.abs(e.clientX - startX) > 10) window.isSwiping = true;
    });

    cardStack.addEventListener("mouseup", (e) => {
      if (!isDragging) return;
      let endX = e.clientX;
      if (window.isSwiping) {
        handleSwipe(startX, endX);
      }
      isDragging = false;
      setTimeout(() => (window.isSwiping = false), 50);
    });

    cardStack.addEventListener("mouseleave", () => {
      isDragging = false;
    });

    function handleSwipe(start, end) {
      const threshold = 40;
      if (start - end > threshold) {
        swipeNext(); // Swipe left -> Next
      } else if (end - start > threshold) {
        swipePrev(); // Swipe right -> Prev
      }
    }
  }
  // --- End Swipe Stack Logic ---

  // ── Modal Like Button handler ──
  const modalLikeBtn = document.getElementById("modal-like-btn");
  if (modalLikeBtn) {
    modalLikeBtn.addEventListener("click", function () {
      // Guest like restriction — show login popup
      if (this.dataset.guest === "true") {
        let popup = document.getElementById("login-like-popup");
        if (!popup) {
          popup = document.createElement("div");
          popup.id = "login-like-popup";
          popup.style.cssText =
            "display:none;position:fixed;inset:0;background:rgba(45,42,53,.5);backdrop-filter:blur(8px);z-index:3000;align-items:center;justify-content:center;";
          popup.innerHTML =
            '<div style="background:var(--card-bg,#fff);border:3px solid var(--text-color,#2d2a35);border-radius:24px;padding:36px 32px;max-width:400px;width:90%;box-shadow:8px 8px 0 var(--text-color,#2d2a35);text-align:center;">' +
            '<div style="font-size:2.5rem;margin-bottom:12px;">\u2764\uFE0F</div>' +
            '<h3 style="font-size:1.4rem;font-weight:900;margin-bottom:10px;font-family:var(--font-main,Outfit,sans-serif);">Pehle Login \u2014 Then Like Bacha \uD83D\uDE0F</h3>' +
            '<p style="font-weight:600;color:#555;margin-bottom:24px;font-family:var(--font-main,Outfit,sans-serif);">Login karke apna like save karo!</p>' +
            '<div style="display:flex;gap:12px;flex-wrap:wrap;">' +
            "<button onclick=\"document.getElementById('login-like-popup').style.display='none'\" style=\"flex:1;padding:14px;background:var(--bg-color,#FDFBF7);border:3px solid var(--text-color,#2d2a35);border-radius:14px;font-family:var(--font-main,Outfit,sans-serif);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:4px 4px 0 var(--text-color,#2d2a35);\">Cancel</button>" +
            '<a href="login.php" style="flex:1;padding:14px;background:var(--primary-color,#E6D7FF);border:3px solid var(--text-color,#2d2a35);border-radius:14px;font-weight:800;font-size:1rem;cursor:pointer;box-shadow:4px 4px 0 var(--text-color,#2d2a35);display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:var(--text-color,#2d2a35);font-family:var(--font-main,Outfit,sans-serif);"><i class="fa-brands fa-google" style="margin-right:8px;"></i> Login with Google</a>' +
            "</div></div>";
          document.body.appendChild(popup);
        }
        popup.style.display = "flex";
        return;
      }

      const promptId = this.dataset.promptId;
      if (!promptId) return;

      const isLiked = this.classList.contains("liked-active");
      const countSpan = document.getElementById("modal-like-count");
      // Sync card elements
      const cardEl = document.querySelector(`.card[data-id="${promptId}"]`);
      const cardCountSpan = cardEl ? cardEl.querySelector(".like-count") : null;
      const cardHeartIcon = cardEl
        ? cardEl.querySelector(".card-like-display i")
        : null;
      const cardLikeDisplay = cardEl
        ? cardEl.querySelector(".card-like-display")
        : null;

      // Optimistic UI
      this.classList.toggle("liked-active");
      this.classList.add("popped");
      setTimeout(() => this.classList.remove("popped"), 300);
      const cur = parseInt(countSpan ? countSpan.textContent : 0) || 0;
      const newCount = isLiked ? Math.max(0, cur - 1) : cur + 1;
      if (countSpan) countSpan.textContent = newCount;
      if (cardCountSpan) cardCountSpan.textContent = newCount;
      // Sync card heart color immediately
      if (cardHeartIcon)
        cardHeartIcon.classList.toggle("liked-heart", !isLiked);
      if (cardLikeDisplay)
        cardLikeDisplay.classList.toggle("is-liked-active", !isLiked);

      const fd = new FormData();
      fd.append("prompt_id", promptId);
      fetch("like.php", { method: "POST", body: fd })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            if (data.action === 'liked' && typeof gtag !== 'undefined') {
              gtag('event', 'prompt_like', { prompt_id: promptId, prompt_title: (currentCardElement ? (currentCardElement.dataset.title || '') : '') });
            }
            if (countSpan) countSpan.textContent = data.likes_count;
            if (cardCountSpan) cardCountSpan.textContent = data.likes_count;
            this.classList.toggle("liked-active", data.action === "liked");
            // Sync card heart color from server
            if (cardHeartIcon)
              cardHeartIcon.classList.toggle(
                "liked-heart",
                data.action === "liked",
              );
            if (cardLikeDisplay)
              cardLikeDisplay.classList.toggle(
                "is-liked-active",
                data.action === "liked",
              );
          } else {
            // revert
            this.classList.toggle("liked-active");
            if (countSpan) countSpan.textContent = cur;
            if (cardCountSpan) cardCountSpan.textContent = cur;
            if (cardHeartIcon)
              cardHeartIcon.classList.toggle("liked-heart", isLiked);
            if (cardLikeDisplay)
              cardLikeDisplay.classList.toggle("is-liked-active", isLiked);
          }
        })
        .catch(() => {
          this.classList.toggle("liked-active");
          if (countSpan) countSpan.textContent = cur;
          if (cardHeartIcon)
            cardHeartIcon.classList.toggle("liked-heart", isLiked);
          if (cardLikeDisplay)
            cardLikeDisplay.classList.toggle("is-liked-active", isLiked);
        });
    });
  }

  cards.forEach((card) => {
    card.addEventListener("click", (e) => {
      if (window.isSwiping) {
        e.preventDefault();
        e.stopPropagation();
        return;
      }

      // Prevent opening modal when clicking like button or category pill
      if (
        e.target.closest(".like-btn") ||
        e.target.closest(".card-like-display") ||
        e.target.closest(".card-category-pill")
      ) {
        e.stopPropagation();
        return;
      }

      // Navigate to prompt page (live site only — gallery handles its own clicks)
      const _slug = card.dataset.slug;
      const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
      const isGalleryPage = document.body.classList.contains('page-gallery');
      if (_slug && !isLocal && !isGalleryPage) {
        window.location.href = '/prompts/' + _slug;
        return;
      }

      currentPromptId = card.dataset.id;
      currentCardElement = card;

      // Populate Modal Info
      const modalImage = document.getElementById("modal-image");
      const modalTitle = document.getElementById("modal-title");
      const modalReelLink = document.getElementById("modal-reel-link");
      const wantCodeSection = document.getElementById("modal-want-code");
      const unlockArea = document.getElementById("modal-unlock-area");
      const unlockedArea = document.getElementById("modal-unlocked-area");
      const unlockedText = document.getElementById("modal-unlocked-text");
      const saveBtn = document.getElementById("modal-save-btn");

      if (modalImage) {
        modalImage.src = card.dataset.image;
        // Add blur if unreleased and locked
        if (
          card.dataset.promptType === "unreleased" &&
          card.dataset.unlocked !== "true"
        ) {
          modalImage.style.filter = "blur(5px)";
          modalImage.style.transition = "filter 0.3s ease-out";
        } else {
          modalImage.style.filter = "";
          modalImage.style.transition = "";
        }
      }
      if (modalTitle) modalTitle.textContent = card.dataset.title;
      // Best Works In badge
      const bwiBadge = document.getElementById('modal-bwi-badge');
      if (bwiBadge) {
        const bwi = card.dataset.bestWorksIn || '';
        if (bwi === 'nano_banana') {
          bwiBadge.innerHTML = '<span class="pp-bwi-badge pp-bwi-nano" style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:999px;font-size:.74rem;font-weight:700;background:#ffe066;color:#2d2a35;border:1px solid rgba(47,65,86,.12);"><i class="fa-solid fa-banana" style="color:#c47a00"></i> Best in Nano Banana</span>';
        } else if (bwi === 'chatgpt') {
          bwiBadge.innerHTML = '<span style="display:inline-flex;align-items:center;gap:6px;background:#10a37f;color:#fff;border:2px solid #2d2a35;border-radius:20px;padding:4px 14px;font-size:.78rem;font-weight:900;box-shadow:2px 2px 0 #2d2a35;">✦ Best in ChatGPT</span>';
        } else {
          bwiBadge.innerHTML = '';
        }
      }
      // Assets section
      const assetsArea = document.getElementById('modal-assets-area');
      if (assetsArea) {
        const aTitle = card.dataset.assetTitle || '';
        let aImages = [];
        try { aImages = JSON.parse(card.dataset.assetImages || '[]'); } catch(e) {}
        if (aTitle || aImages.length > 0) {
          const aTitleEl = document.getElementById('modal-asset-title');
          if (aTitleEl) aTitleEl.textContent = aTitle || 'Assets';
          const aImgContainer = document.getElementById('modal-asset-images');
          if (aImgContainer) {
            aImgContainer.innerHTML = aImages.map((src, i) =>
              `<div style="position:relative;flex:1;min-width:100px;max-width:160px;">
                <img src="${src}" alt="Asset ${i+1}" style="width:100%;aspect-ratio:3/4;object-fit:cover;border-radius:12px;border:var(--border-width,3px) solid var(--text-color,#2d2a35);display:block;">
                <a href="${src}" download="asset_${i+1}" style="position:absolute;bottom:8px;right:8px;background:var(--text-color,#2d2a35);color:var(--bg-color,#fdfbf7);border-radius:10px;padding:6px 10px;font-size:.72rem;font-weight:900;text-decoration:none;display:flex;align-items:center;gap:5px;box-shadow:2px 2px 0 rgba(0,0,0,.3);font-family:var(--font-main);">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 16l-5-5h3V4h4v7h3l-5 5zm-7 4v-2h14v2H5z"/></svg> Save
                </a>
              </div>`
            ).join('');
          }
          assetsArea.style.display = 'block';
        } else {
          assetsArea.style.display = 'none';
        }
      }

      if (saveBtn) {
        saveBtn.dataset.promptId = card.dataset.id;
        applySaveBtnState(saveBtn, card.dataset.saved === "true");
      }
      // Populate modal like button
      if (modalLikeBtn) {
        modalLikeBtn.dataset.promptId = card.dataset.id;
        const cardLikeBtn = card.querySelector(".card-like-display");
        const isLiked = cardLikeBtn && cardLikeBtn.dataset.liked === "true";
        const likeCount = cardLikeBtn
          ? cardLikeBtn.querySelector(".like-count")?.textContent || "0"
          : "0";
        modalLikeBtn.classList.toggle("liked-active", isLiked);
        const modalCountEl = document.getElementById("modal-like-count");
        if (modalCountEl) modalCountEl.textContent = likeCount;
      }

      const pType = (card.dataset.promptType || "").trim();

      // --- Pre-unlocked check ---
      if (card.dataset.unlocked === "true") {
        if (wantCodeSection) wantCodeSection.style.display = "none";
        if (unlockArea) unlockArea.style.display = "none";
        if (unlockedArea) {
          unlockedArea.style.display = "flex";
          if (unlockedText)
            unlockedText.textContent = card.dataset.promptText || "";
        }
      } else {
        // Hide reel link by default; show for SCP only
        if (wantCodeSection) wantCodeSection.style.display = "none";
        if (unlockedArea) unlockedArea.style.display = "none";
        if (unlockArea) unlockArea.style.display = "block";

        // ══════════════════════════════════════════
        // IVP — Insta Viral: 7-number math challenge
        // ══════════════════════════════════════════
        if (pType === "insta_viral") {
          // Fetch challenge from server — answer is stored in session server-side
          if (unlockArea)
            unlockArea.innerHTML = `<p style="font-weight:700;text-align:center;padding:20px;">🧮 Loading challenge...</p>`;
          const _cfd = new FormData();
          _cfd.append("action", "get_challenge");
          _cfd.append("prompt_id", currentPromptId);
          fetch("unlock.php", { method: "POST", body: _cfd })
            .then((r) => r.json())
            .then((challenge) => {
              const n1 = challenge.n1,
                n2 = challenge.n2,
                n3 = challenge.n3,
                n4 = challenge.n4;
              const ans = n1 + n2 + n3 + n4;

              // 4 MCQ options — all unique, answer always included
              let opts = new Set([ans]);
              while (opts.size < 4) {
                const decoy = ans + (Math.floor(Math.random() * 10) - 5);
                if (decoy !== ans && decoy >= 0) opts.add(decoy);
              }
              const options = [...opts].sort(() => Math.random() - 0.5);

              let html = `<p style="font-weight:900;font-size:1.1rem;margin-bottom:12px;color:#d03030;">🧮 MATH CHALLENGE!</p>`;
              html += `<p style="font-weight:700;font-size:1.6rem;margin-bottom:18px;letter-spacing:1px;">${n1} + ${n2} + ${n3} + ${n4} = ?</p>`;
              html += `<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">`;
              options.forEach((opt) => {
                html += `<button class="math-opt comic-btn-small" data-ans="${ans}" data-val="${opt}" style="background:var(--secondary-color);color:var(--text-color);font-size:1.1rem;padding:14px 8px;font-weight:900;">${opt}</button>`;
              });
              html += `</div>`;
              html += `<div id="math-error" style="color:#d03030;font-weight:800;margin-top:10px;display:none;">Admission lo school mein... itna sa nhi aata 😂</div>`;

              if (unlockArea) unlockArea.innerHTML = html;

              setTimeout(() => {
                document.querySelectorAll(".math-opt").forEach((btn) => {
                  btn.addEventListener("click", function () {
                    if (
                      parseInt(this.dataset.val) === parseInt(this.dataset.ans)
                    ) {
                      this.style.background = "#2ecc71";
                      this.style.color = "#fff";
                      unlockInstaViral(currentPromptId, ans); // send answer to server
                    } else {
                      this.style.background = "#e74c3c";
                      this.style.color = "#fff";
                      document.getElementById("math-error").style.display =
                        "block";
                      triggerEmojiRain("😂", 15);
                      // Regenerate after 1.5s on wrong answer
                      setTimeout(() => {
                        const card = currentCardElement;
                        if (card) card.click();
                      }, 1500);
                    }
                  });
                });
              }, 50);
            })
            .catch(() => {
              if (unlockArea)
                unlockArea.innerHTML = `<p style="color:#d03030;font-weight:700;text-align:center;padding:20px;">Failed to load challenge. Please close and try again.</p>`;
            });

          // ══════════════════════════════════════════
          // URP — Unreleased: Love Bar inside modal
          // ══════════════════════════════════════════
        } else if (pType === "unreleased") {
          // Notify server that love challenge has started (for time-based verification)
          const _ifd = new FormData();
          _ifd.append("action", "init_love");
          _ifd.append("prompt_id", currentPromptId);
          fetch("unlock.php", { method: "POST", body: _ifd });

          const LOVE_THRESHOLD =
            typeof isLoggedIn !== "undefined" && isLoggedIn ? 20 : 90;
          let taps = 0;

          if (unlockArea)
            unlockArea.innerHTML = `
                        <p style="font-weight:900;font-size:1rem;margin-bottom:14px;color:#d03030;">
                            ❤️ Show Some Love to Unlock!
                        </p>
                        <p style="font-weight:600;font-size:0.85rem;color:#888;margin-bottom:14px;">
                            ${LOVE_THRESHOLD === 90 ? "⚠️ Login to unlock with just 20 taps!" : `Tap ${LOVE_THRESHOLD} times to reveal this prompt`}
                        </p>
                        <div style="background:#f0f0f0;border:2px solid var(--text-color);border-radius:20px;overflow:hidden;height:16px;margin-bottom:12px;box-shadow:2px 2px 0 var(--text-color);">
                            <div id="urp-bar-fill" style="height:100%;width:0%;background:linear-gradient(90deg,#ff6b9d,#ff3b6e);transition:width 0.2s;border-radius:20px;"></div>
                        </div>
                        <div style="text-align:center;font-weight:800;font-size:0.9rem;margin-bottom:14px;">
                            <span id="urp-tap-count">0</span> / ${LOVE_THRESHOLD} taps
                        </div>
                        <button id="urp-tap-btn" style="width:100%;padding:16px;background:var(--secondary-color);border:var(--border-width) solid var(--text-color);border-radius:16px;font-family:var(--font-main);font-weight:900;font-size:1.2rem;cursor:pointer;box-shadow:var(--shadow-comic);transition:transform 0.1s;">
                            ❤️ TAP TO LOVE
                        </button>
                    `;

          setTimeout(() => {
            const tapBtn = document.getElementById("urp-tap-btn");
            const fill = document.getElementById("urp-bar-fill");
            const counter = document.getElementById("urp-tap-count");
            if (!tapBtn) return;

            tapBtn.addEventListener("click", () => {
              if (taps >= LOVE_THRESHOLD) return;
              taps++;
              const pct = (taps / LOVE_THRESHOLD) * 100;
              fill.style.width = pct + "%";
              counter.textContent = taps;

              // Heartbeat bounce
              tapBtn.style.transform = "scale(0.94)";
              setTimeout(() => (tapBtn.style.transform = ""), 100);

              // Progressive blur reduction
              const modalImg = document.getElementById("modal-image");
              if (modalImg) {
                const blurVal = 5 - 5 * (pct / 100);
                modalImg.style.filter = `blur(${blurVal}px)`;
              }

              // Floating heart
              const h = document.createElement("span");
              h.textContent = "❤️";
              h.style.cssText = `position:fixed;pointer-events:none;font-size:1.4rem;animation:floatHeart 1s ease-out forwards;left:${40 + Math.random() * 20}%;top:60%;z-index:9999;`;
              document.body.appendChild(h);
              setTimeout(() => h.remove(), 1000);

              if (taps >= LOVE_THRESHOLD) {
                tapBtn.textContent = "🔓 Unlocking...";
                tapBtn.disabled = true;
                if (modalImg) modalImg.style.filter = ""; // fully clear

                // Update underlying card immediately
                if (currentCardElement) {
                  const bgImg =
                    currentCardElement.querySelector(".card-bg-image");
                  if (bgImg) bgImg.style.filter = "";
                }

                unlockUnreleased(currentPromptId);
              }
            });
          }, 50);

          // ══════════════════════════════════════════
          // AUP — Already Uploaded Prompts: 9 Taps
          // ══════════════════════════════════════════
        } else if (pType === "already_uploaded") {
          const LOVE_THRESHOLD = 9; // Strictly 9 taps for everyone
          let taps = 0;

          if (unlockArea)
            unlockArea.innerHTML = `
                        <p style="font-weight:900;font-size:1rem;margin-bottom:14px;color:#00509e;">
                            👆 Tap to Unlock Prompt!
                        </p>
                        <p style="font-weight:600;font-size:0.85rem;color:#888;margin-bottom:14px;">
                            Tap 9 times to reveal this already uploaded prompt
                        </p>
                        <div style="background:#f0f0f0;border:2px solid var(--text-color);border-radius:20px;overflow:hidden;height:16px;margin-bottom:12px;box-shadow:2px 2px 0 var(--text-color);">
                            <div id="aup-bar-fill" style="height:100%;width:0%;background:linear-gradient(90deg,#80c1ff,#007ab8);transition:width 0.2s;border-radius:20px;"></div>
                        </div>
                        <div style="text-align:center;font-weight:800;font-size:0.9rem;margin-bottom:14px;">
                            <span id="aup-tap-count">0</span> / ${LOVE_THRESHOLD} taps
                        </div>
                        <button id="aup-tap-btn" style="width:100%;padding:16px;background:#e6f2ff;border:var(--border-width) solid var(--text-color);border-radius:16px;font-family:var(--font-main);font-weight:900;font-size:1.2rem;cursor:pointer;box-shadow:var(--shadow-comic);color:#00509e;transition:transform 0.1s;">
                            👆 TAP
                        </button>
                    `;

          setTimeout(() => {
            const tapBtn = document.getElementById("aup-tap-btn");
            const fill = document.getElementById("aup-bar-fill");
            const counter = document.getElementById("aup-tap-count");
            if (!tapBtn) return;

            tapBtn.addEventListener("click", () => {
              if (taps >= LOVE_THRESHOLD) return;
              taps++;
              const pct = (taps / LOVE_THRESHOLD) * 100;
              fill.style.width = pct + "%";
              counter.textContent = taps;

              tapBtn.style.transform = "scale(0.94)";
              setTimeout(() => (tapBtn.style.transform = ""), 100);

              const modalImg = document.getElementById("modal-image");

              if (taps >= LOVE_THRESHOLD) {
                tapBtn.textContent = "🔓 Unlocking...";
                tapBtn.disabled = true;
                if (modalImg) modalImg.style.filter = "";

                if (currentCardElement) {
                  const bgImg =
                    currentCardElement.querySelector(".card-bg-image");
                  if (bgImg) bgImg.style.filter = "";
                }

                unlockAlreadyUploaded(currentPromptId);
              }
            });
          }, 50);

          // ══════════════════════════════════════════
          // SCP — Secret Code: Code input
          // ══════════════════════════════════════════
        } else {
          if (modalReelLink) modalReelLink.href = `all_codes.php#code-${currentPromptId}`;
          if (wantCodeSection) wantCodeSection.style.display = "block";
          if (unlockArea) {
            unlockArea.innerHTML = `
                            <p style="font-weight:700;margin-bottom:16px;color:#555;">Enter the secret code to reveal this prompt.</p>
                            <input type="text" id="unlock-code-input" placeholder="6-Letter Code" maxlength="6">
                            <button id="submit-code"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt</button>
                        `;
          }
          const ci = document.getElementById("unlock-code-input");
          const scb = document.getElementById("submit-code");
          if (ci) {
            ci.value = "";
            ci.focus();
            ci.addEventListener(
              "input",
              (e) => (e.target.value = e.target.value.toUpperCase()),
            );
            ci.addEventListener("keypress", (e) => {
              if (e.key === "Enter") verifyCode();
            });
          }
          if (scb) scb.addEventListener("click", verifyCode);
        }
      }

      // Show modal smoothly
      if (modalOverlay) {
        modalOverlay.style.display = "flex";
        setTimeout(() => modalOverlay.classList.add("show"), 10);
      }

      document.dispatchEvent(
        new CustomEvent("modalOpened", {
          detail: { promptId: card.dataset.id },
        }),
      );

      if (typeof gtag !== 'undefined') gtag('event', 'prompt_view', { prompt_id: card.dataset.id, prompt_title: card.dataset.title || '' });
      const _vf = new FormData(); _vf.append('action','view'); _vf.append('prompt_id', card.dataset.id);
      fetch('track_action.php', { method:'POST', body:_vf }).catch(()=>{});
    });
  });

  if (closeModalBtn && modalOverlay) {
    function hideModal() {
      modalOverlay.classList.remove("show");
      setTimeout(() => (modalOverlay.style.display = "none"), 300);
    }

    closeModalBtn.addEventListener("click", hideModal);

    modalOverlay.addEventListener("click", (e) => {
      if (e.target === modalOverlay) hideModal();
    });
  }

  function verifyCode() {
    const codeInput = document.getElementById("unlock-code-input");
    const submitCodeBtn = document.getElementById("submit-code");
    if (!codeInput) return;
    const code = codeInput.value.trim();
    if (!code) {
      showComicAlert("Please enter a code!", "error");
      return;
    }

    if (submitCodeBtn) {
      submitCodeBtn.style.transform = "translate(4px, 4px)";
      setTimeout(() => (submitCodeBtn.style.transform = ""), 100);
    }

    const formData = new FormData();
    formData.append("action", "verify");
    formData.append("prompt_id", currentPromptId);
    formData.append("code", code);

    fetch("unlock.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          if (typeof gtag !== 'undefined') gtag('event', 'prompt_unlock', { method: 'secret_code', prompt_id: currentPromptId });
          // Update Modal UI
          const unlockArea = document.getElementById("modal-unlock-area");
          const unlockedArea = document.getElementById("modal-unlocked-area");
          const unlockedText = document.getElementById("modal-unlocked-text");
          const wantCode = document.getElementById("modal-want-code");
          const saveBtn = document.getElementById("modal-save-btn");

          if (unlockArea) unlockArea.style.display = "none";
          if (wantCode) wantCode.style.display = "none";
          if (unlockedArea) {
            unlockedArea.style.display = "flex";
            if (unlockedText) unlockedText.textContent = data.prompt_text;
          }
          // Set save button promptId
          if (saveBtn) {
            saveBtn.dataset.promptId = currentPromptId;
            const isSaved =
              currentCardElement &&
              currentCardElement.dataset.saved === "true";
            applySaveBtnState(saveBtn, isSaved);
          }

          // Update card lock icon
          if (currentCardElement) {
            const lockIcon =
              currentCardElement.querySelector(".card-lock-icon");
            if (lockIcon) {
              lockIcon.style.background = "var(--primary-color)";
              lockIcon.innerHTML =
                '<i class="fa-solid fa-check" style="font-size:14px;"></i>';
            }
            currentCardElement.classList.add("glow-flash");
            setTimeout(
              () => currentCardElement.classList.remove("glow-flash"),
              500,
            );
          }
          // \ud83c\udf89 Confetti burst on every unlock
          triggerConfetti();
          // First unlock celebration
          if (typeof checkFirstUnlock === "function") checkFirstUnlock();
        } else {
          // Show comic "NO NO BACHA" popup
          const popup = document.getElementById("wrong-code-popup");
          if (popup) {
            popup.classList.add("show");
            // Auto-dismiss after 4s
            setTimeout(() => popup.classList.remove("show"), 4000);
          } else {
            showComicAlert("Invalid Code! Try again.", "error");
          }
          const ci2 = document.getElementById("unlock-code-input");
          if (ci2) {
            ci2.value = "";
            ci2.focus();
          }
          // Shake input
          const scInput = document.getElementById("unlock-code-input");
          if (scInput) {
            scInput.style.animation = "none";
            setTimeout(() => (scInput.style.animation = "shake 0.4s"), 10);
          }
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showComicAlert("Something went wrong!", "error");
      });
  }

  // Handle Copy buttons using event delegation
  document.addEventListener("click", (e) => {
    // Copy Button Logic
    if (e.target.closest(".copy-btn") || e.target.closest(".pp-copy-btn")) {
      const btn = e.target.closest(".copy-btn") || e.target.closest(".pp-copy-btn");
      // Try modal first, then closest unlocked-state, then prompt page
      let textToCopy = "";
      const modalText = document.getElementById("modal-unlocked-text");
      const ppText = document.getElementById("pp-prompt-text");
      if (modalText && modalText.textContent) {
        textToCopy = modalText.textContent;
      } else if (ppText && ppText.textContent) {
        textToCopy = ppText.textContent;
      } else {
        const unlockedState = btn.closest(".unlocked-state");
        if (unlockedState)
          textToCopy =
            unlockedState.querySelector(".unlocked-text")?.textContent || "";
      }

      function fallbackCopy(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
          document.execCommand("copy");
        } catch (err) {}
        textArea.remove();
      }

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard
          .writeText(textToCopy)
          .catch(() => fallbackCopy(textToCopy));
      } else {
        fallbackCopy(textToCopy);
      }

      const _cpid = currentPromptId;
      if (_cpid) {
        if (typeof gtag !== 'undefined') gtag('event', 'prompt_copy', { prompt_id: _cpid });
        const _cf = new FormData(); _cf.append('action','copy'); _cf.append('prompt_id', _cpid);
        fetch('track_action.php', { method:'POST', body:_cf }).catch(()=>{});
      }

      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
      btn.style.backgroundColor = "#00ff66";
      btn.style.color = "#000";

      setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.style.backgroundColor = "";
        btn.style.color = "";
      }, 2000);
    }

    // Like button clicks are handled by attachLikeListeners() above — skip here
    if (e.target.closest(".like-btn")) return;
  });

  function unlockInstaViral(promptId, userAnswer) {
    const formData = new FormData();
    formData.append("action", "insta_viral");
    formData.append("prompt_id", promptId);
    formData.append("user_answer", userAnswer); // server verifies this against session

    fetch("unlock.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          if (typeof gtag !== 'undefined') gtag('event', 'prompt_unlock', { method: 'insta_viral', prompt_id: promptId });
          const unlockArea = document.getElementById("modal-unlock-area");
          const unlockedArea = document.getElementById("modal-unlocked-area");
          const unlockedText = document.getElementById("modal-unlocked-text");
          const wantCode = document.getElementById("modal-want-code");
          const saveBtn = document.getElementById("modal-save-btn");

          if (unlockArea) unlockArea.style.display = "none";
          if (wantCode) wantCode.style.display = "none";
          if (unlockedArea) {
            unlockedArea.style.display = "flex";
            if (unlockedText) unlockedText.textContent = data.prompt_text;
          }
          if (saveBtn) {
            saveBtn.dataset.promptId = promptId;
            const isSaved =
              currentCardElement &&
              currentCardElement.dataset.saved === "true";
            applySaveBtnState(saveBtn, isSaved);
          }

          if (currentCardElement) {
            const lockIcon =
              currentCardElement.querySelector(".card-lock-icon");
            if (lockIcon) {
              lockIcon.style.background = "var(--primary-color)";
              lockIcon.innerHTML =
                '<i class="fa-solid fa-check" style="font-size:14px;"></i>';
            }
            currentCardElement.classList.add("glow-flash");
            setTimeout(
              () => currentCardElement.classList.remove("glow-flash"),
              500,
            );
            currentCardElement.dataset.unlocked = "true";
            currentCardElement.dataset.promptText = data.prompt_text;
          }
          // \ud83c\udf89 Confetti burst on every unlock
          triggerConfetti();
          // First unlock celebration
          if (typeof checkFirstUnlock === "function") checkFirstUnlock();
        } else {
          showComicAlert("Failed to unlock!", "error");
        }
      });
  }

  function triggerEmojiRain(emoji, count = 15) {
    for (let i = 0; i < count; i++) {
      const h = document.createElement("span");
      h.textContent = emoji;
      h.style.cssText = `position:fixed; z-index:9999; pointer-events:none; font-size:${20 + Math.random() * 20}px; animation: floatHeart ${0.8 + Math.random() * 0.8}s ease-out forwards; left:${Math.random() * 100}vw; top:100vh;`;
      document.body.appendChild(h);
      setTimeout(() => h.remove(), 2000);
    }
  }

  // ── Confetti Burst on Unlock ──
  function triggerConfetti(count = 60) {
    if (!document.getElementById("confetti-keyframes")) {
      const st = document.createElement("style");
      st.id = "confetti-keyframes";
      st.textContent =
        "@keyframes confettiFall{0%{transform:translateY(0) rotate(0deg);opacity:1}100%{transform:translateY(110vh) rotate(720deg);opacity:.85}}";
      document.head.appendChild(st);
    }
    const colors = [
      "#ff6b9d",
      "#7c3aed",
      "#fbbf24",
      "#34d399",
      "#60a5fa",
      "#f87171",
      "#fb923c",
      "#e879f9",
    ];
    for (let i = 0; i < count; i++) {
      const p = document.createElement("div");
      const color = colors[Math.floor(Math.random() * colors.length)];
      const size = 8 + Math.random() * 6;
      p.style.cssText = `position:fixed;z-index:99999;pointer-events:none;width:${size}px;height:${size * 0.4}px;background:${color};left:${Math.random() * 100}vw;top:-20px;animation:confettiFall ${1.8 + Math.random() * 1.5}s ${Math.random() * 0.4}s ease-in forwards;border-radius:2px;`;
      document.body.appendChild(p);
      setTimeout(() => p.remove(), 4000);
    }
  }

  // ── Share Current Prompt ──
  function shareCurrentPrompt() {
    const id = currentPromptId;
    if (!id) return;
    const card = currentCardElement;
    const title = (card && card.dataset.title) || "AI Couple Prompt";
    const url = `${window.location.origin}/card.php?id=${id}`;

    if (navigator.share) {
      navigator
        .share({
          title: `Arigato Devan Prompts \u2014 ${title}`,
          text: `Check out this AI couple prompt: ${title}`,
          url: url,
        })
        .catch(() => {});
      return;
    }

    function fallbackCopy(text) {
      const ta = document.createElement("textarea");
      ta.value = text;
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand("copy");
      } catch (e) {}
      ta.remove();
    }

    const _trackShare = () => {
      if (typeof gtag !== 'undefined') gtag('event', 'prompt_share', { prompt_id: id, prompt_title: title });
      const _sf = new FormData(); _sf.append('action','share'); _sf.append('prompt_id', id);
      fetch('track_action.php', { method:'POST', body:_sf }).catch(()=>{});
    };

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard
        .writeText(url)
        .then(() => { showComicAlert("Share link copied! \ud83d\udd17", "success"); _trackShare(); })
        .catch(() => {
          fallbackCopy(url);
          showComicAlert("Share link copied! \ud83d\udd17", "success");
          _trackShare();
        });
    } else {
      fallbackCopy(url);
      showComicAlert("Share link copied! \ud83d\udd17", "success");
      _trackShare();
    }
  }

  // Inject Share button as floating top-left button on modal
  // (visible on both locked and unlocked cards — works on mobile too)
  (function injectShareButton() {
    const modalContent = document.querySelector("#unlock-modal .modal-content");
    if (!modalContent || document.getElementById("modal-share-btn")) return;

    // Inject stylesheet ONCE — uses !important to beat .modal-content button rules
    if (!document.getElementById("adp-share-btn-styles")) {
      const st = document.createElement("style");
      st.id = "adp-share-btn-styles";
      st.textContent =
        "#modal-share-btn{" +
        "position:absolute !important;" +
        "top:12px !important;" +
        "left:12px !important;" +
        "right:auto !important;" +
        "bottom:auto !important;" +
        "z-index:9999 !important;" +
        "width:42px !important;" +
        "height:42px !important;" +
        "min-width:42px !important;" +
        "max-width:42px !important;" +
        "padding:0 !important;" +
        "margin:0 !important;" +
        "background:#a7f3d0 !important;" +
        "color:var(--text-color) !important;" +
        "border:2px solid var(--text-color) !important;" +
        "border-radius:50% !important;" +
        "cursor:pointer !important;" +
        "box-shadow:3px 3px 0 var(--text-color) !important;" +
        "display:flex !important;" +
        "align-items:center !important;" +
        "justify-content:center !important;" +
        "font-family:var(--font-main) !important;" +
        "font-size:0.95rem !important;" +
        "font-weight:800 !important;" +
        "line-height:1 !important;" +
        "transform:none !important;" +
        "transition:transform .12s ease, box-shadow .12s ease !important;" +
        "box-sizing:border-box !important;" +
        "-webkit-tap-highlight-color:transparent !important;" +
        "}" +
        "#modal-share-btn:hover{transform:none !important;background:#86efac !important;box-shadow:4px 4px 0 var(--text-color) !important;}" +
        "#modal-share-btn:active,#modal-share-btn.is-pressed{transform:translate(2px,2px) !important;box-shadow:1px 1px 0 var(--text-color) !important;}";
      document.head.appendChild(st);
    }

    // Ensure modal-content is positioned so absolute child anchors correctly
    if (window.getComputedStyle(modalContent).position === "static") {
      modalContent.style.position = "relative";
    }

    const shareBtn = document.createElement("button");
    shareBtn.id = "modal-share-btn";
    shareBtn.type = "button";
    shareBtn.title = "Share this prompt";
    shareBtn.setAttribute("aria-label", "Share this prompt");
    shareBtn.innerHTML = '<i class="fa-solid fa-share-nodes"></i>';
    shareBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      shareCurrentPrompt();
    });

    modalContent.appendChild(shareBtn);
  })();

  function unlockAlreadyUploaded(promptId) {
    const formData = new FormData();
    formData.append("action", "already_uploaded");
    formData.append("prompt_id", promptId);

    fetch("unlock.php", { method: "POST", body: formData })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          if (typeof gtag !== 'undefined') gtag('event', 'prompt_unlock', { method: 'already_uploaded', prompt_id: promptId });
          const unlockArea = document.getElementById("modal-unlock-area");
          const unlockedArea = document.getElementById("modal-unlocked-area");
          const unlockedText = document.getElementById("modal-unlocked-text");
          const wantCode = document.getElementById("modal-want-code");
          const saveBtn = document.getElementById("modal-save-btn");

          if (unlockArea) unlockArea.style.display = "none";
          if (wantCode) wantCode.style.display = "none";
          if (unlockedArea) {
            unlockedArea.style.display = "flex";
            if (unlockedText) unlockedText.textContent = data.prompt_text;
          }

          if (currentCardElement) {
            currentCardElement.dataset.unlocked = "true";
            currentCardElement.dataset.promptText = data.prompt_text;
            const lockIcon =
              currentCardElement.querySelector(".card-lock-icon");
            if (lockIcon) lockIcon.remove();
          }

          // Reflect persisted saved state on the SAVE button
          if (saveBtn) {
            saveBtn.dataset.promptId = promptId;
            const isSaved =
              currentCardElement &&
              currentCardElement.dataset.saved === "true";
            applySaveBtnState(saveBtn, isSaved);
          }
          // \ud83c\udf89 Confetti burst on every unlock
          triggerConfetti();
        } else {
          showComicAlert("Could not unlock. Please try again later.");
        }
      });
  }

  function unlockUnreleased(promptId) {
    const formData = new FormData();
    formData.append("action", "unreleased");
    formData.append("prompt_id", promptId);

    fetch("unlock.php", { method: "POST", body: formData })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          if (typeof gtag !== 'undefined') gtag('event', 'prompt_unlock', { method: 'unreleased', prompt_id: promptId });
          const unlockArea = document.getElementById("modal-unlock-area");
          const unlockedArea = document.getElementById("modal-unlocked-area");
          const unlockedText = document.getElementById("modal-unlocked-text");
          const wantCode = document.getElementById("modal-want-code");
          const saveBtn = document.getElementById("modal-save-btn");

          if (unlockArea) unlockArea.style.display = "none";
          if (wantCode) wantCode.style.display = "none";
          if (unlockedArea) {
            unlockedArea.style.display = "flex";
            if (unlockedText) unlockedText.textContent = data.prompt_text;
          }
          if (saveBtn) {
            saveBtn.dataset.promptId = promptId;
            const isSaved =
              currentCardElement &&
              currentCardElement.dataset.saved === "true";
            applySaveBtnState(saveBtn, isSaved);
          }
          if (currentCardElement) {
            const lockIcon =
              currentCardElement.querySelector(".card-lock-icon");
            if (lockIcon) {
              lockIcon.style.background = "var(--primary-color)";
              lockIcon.innerHTML =
                '<i class="fa-solid fa-check" style="font-size:14px;"></i>';
            }
            currentCardElement.classList.add("glow-flash");
            setTimeout(
              () => currentCardElement.classList.remove("glow-flash"),
              500,
            );
            currentCardElement.dataset.unlocked = "true";
            currentCardElement.dataset.promptText = data.prompt_text;
          }
          triggerEmojiRain("❤️", 10);
          // \ud83c\udf89 Confetti burst on every unlock
          triggerConfetti();
          // First unlock celebration
          if (typeof checkFirstUnlock === "function") checkFirstUnlock();
        } else {
          showComicAlert(
            data.message || "Failed to unlock! Try again.",
            "error",
          );
          setTimeout(() => {
            if (currentCardElement) currentCardElement.click();
          }, 1500);
        }
      })
      .catch(() => {
        showComicAlert("Something went wrong!", "error");
        setTimeout(() => {
          if (currentCardElement) currentCardElement.click();
        }, 1500);
      });
  }

  // Logo Interaction Logic
  const logoContainer = document.getElementById("logo-container");
  let tapCount = 0;
  let tapTimeout = null;

  if (logoContainer) {
    logoContainer.addEventListener("click", (e) => {
      if (window.innerWidth > 768) {
        // Desktop: immediate navigation
        window.location.href = "index.php";
        return;
      }

      // Mobile: handle double tap vs single tap
      tapCount++;

      if (tapCount === 1) {
        tapTimeout = setTimeout(() => {
          if (tapCount === 1) {
            window.location.href = "index.php";
          }
          tapCount = 0;
        }, 300); // 300ms wait for a potential second tap
      } else if (tapCount === 2) {
        clearTimeout(tapTimeout);
        tapCount = 0;

        logoContainer.classList.add("triple-tapped"); // reusing the flip CSS class

        // Spawn floating hearts
        for (let i = 0; i < 5; i++) {
          createFloatingHeart(logoContainer);
        }

        setTimeout(() => {
          logoContainer.classList.remove("triple-tapped");
        }, 4000); // Revert after 4s
      }
    });
  }

  function createFloatingHeart(container) {
    const heart = document.createElement("div");
    heart.innerHTML = "❤️";
    heart.className = "floating-heart";

    // Randomize position
    const xOffset = (Math.random() - 0.5) * 50;
    heart.style.left = `calc(50% + ${xOffset}px)`;

    container.appendChild(heart);

    setTimeout(() => {
      heart.remove();
    }, 1500);
  }

  // --- Love Bar Logic (Unreleased Prompts) ---

  document.querySelectorAll(".unreleased-card").forEach((card) => {
    const LOVE_THRESHOLD = parseInt(card.dataset.threshold || 20);
    const tapBtn = card.querySelector(".love-tap-btn");
    const fill = card.querySelector(".love-bar-fill");
    const label = card.querySelector(".tap-count");
    const reveal = card.querySelector(".unreleased-prompt-reveal");
    const loveWrap = card.querySelector(".love-bar-wrap");
    const promptText = card.querySelector(".unreleased-prompt-text");
    const copyBtn = card.querySelector(".unreleased-copy-btn");
    const img = card.querySelector(".unreleased-img");

    if (!tapBtn) return;

    let taps = 0;

    tapBtn.addEventListener("click", () => {
      if (taps >= LOVE_THRESHOLD) return; // Already unlocked

      taps++;
      const pct = (taps / LOVE_THRESHOLD) * 100;

      // Update bar
      fill.style.width = pct + "%";
      label.textContent = taps;

      // Heartbeat animation
      tapBtn.classList.remove("tapped");
      void tapBtn.offsetWidth; // reflow trick
      tapBtn.classList.add("tapped");

      // Spawn floating heart emoji
      const h = document.createElement("span");
      h.textContent = "❤️";
      h.style.cssText = `position:absolute; pointer-events:none; font-size:1.6rem; animation: floatHeart 1s ease-out forwards; left:${40 + Math.random() * 20}%; top:60%;`;
      card.style.position = "relative";
      card.appendChild(h);
      setTimeout(() => h.remove(), 1000);

      if (taps >= LOVE_THRESHOLD) {
        // Unlock!
        setTimeout(() => {
          card.classList.add("unlocked");
          loveWrap.style.display = "none";
          promptText.textContent = card.dataset.prompt || "";
          reveal.style.display = "flex";
          tapBtn.textContent = "🔓 UNLOCKED!";
          fill.style.width = "100%";
        }, 200);
      }
    });

    // Copy button
    if (copyBtn) {
      copyBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        const text = promptText.textContent;

        function fallbackCopy(txt) {
          const textArea = document.createElement("textarea");
          textArea.value = txt;
          textArea.style.position = "fixed";
          textArea.style.left = "-999999px";
          document.body.appendChild(textArea);
          textArea.focus();
          textArea.select();
          try {
            document.execCommand("copy");
          } catch (err) {}
          textArea.remove();
        }

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(text).catch(() => fallbackCopy(text));
        } else {
          fallbackCopy(text);
        }

        const orig = copyBtn.innerHTML;
        copyBtn.innerHTML = "COPIED! ✅";
        copyBtn.style.backgroundColor = "#00ff66";
        copyBtn.style.color = "#000";

        setTimeout(() => {
          copyBtn.innerHTML = orig;
          copyBtn.style.backgroundColor = "";
          copyBtn.style.color = "";
        }, 2000);
      });
    }
  });
  // --- End Love Bar Logic ---

  // --- Logo Desktop Hover Flip ---
  const logoFlipper = document.querySelector(".logo-flipper");
  if (logoFlipper && window.innerWidth > 768) {
    const logoArea = document.getElementById("logo-container");
    if (logoArea) {
      logoArea.addEventListener("mouseenter", () =>
        logoFlipper.classList.add("flipped"),
      );
      logoArea.addEventListener("mouseleave", () =>
        logoFlipper.classList.remove("flipped"),
      );
    }
  }
  // --- End Logo Flip ---

  // --- Nav Dropdown Click Toggle ---
  document.querySelectorAll(".nav-dropdown-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const dropdown = btn.closest(".nav-dropdown");
      const isOpen = dropdown.classList.contains("open");
      // Close all dropdowns first
      document
        .querySelectorAll(".nav-dropdown.open")
        .forEach((d) => d.classList.remove("open"));
      // Toggle current
      if (!isOpen) dropdown.classList.add("open");
    });
  });
  // Close dropdown when clicking outside
  document.addEventListener("click", () => {
    document
      .querySelectorAll(".nav-dropdown.open")
      .forEach((d) => d.classList.remove("open"));
  });
  // --- End Nav Dropdown ---

  // --- Smart Search Function ---
  const searchInput = document.querySelector(".search-bar input");
  const searchContainer = document.querySelector(".search-bar");

  if (searchInput && searchContainer) {
    searchContainer.style.position = "relative";

    // Create dropdown container
    const searchDropdown = document.createElement("div");
    searchDropdown.className = "search-dropdown";
    searchDropdown.style.display = "none";
    searchContainer.appendChild(searchDropdown);

    // Read local cards
    const localCards = Array.from(
      document.querySelectorAll(".card:not(#end-card)"),
    );
    const searchableData = localCards.map((card) => {
      return {
        title: (card.dataset.title || "").toLowerCase(),
        tags: (card.dataset.tags || "").toLowerCase(),
        element: card,
        rawTitle: card.dataset.title || "Untitled",
      };
    });

    searchInput.addEventListener("input", (e) => {
      const query = e.target.value.toLowerCase().trim();
      if (!query) {
        searchDropdown.style.display = "none";
        return;
      }

      const matches = searchableData.filter(
        (item) => item.title.includes(query) || item.tags.includes(query),
      );

      searchDropdown.innerHTML = "";
      if (matches.length > 0) {
        const summary = document.createElement("div");
        summary.style.padding = "12px 16px";
        summary.style.fontWeight = "800";
        summary.style.borderBottom =
          "var(--border-width) solid var(--text-color)";
        summary.style.background = "var(--bg-color)";
        summary.innerHTML =
          '<i class="fa-solid fa-list" style="margin-right:8px; opacity:0.5;"></i> ' +
          matches.length +
          " results found";
        searchDropdown.appendChild(summary);

        // Show up to 5 suggestions
        matches.slice(0, 5).forEach((match) => {
          const item = document.createElement("div");
          item.className = "search-suggestion";
          item.innerHTML =
            '<i class="fa-solid fa-search" style="margin-right:8px; opacity:0.5;"></i> ' +
            match.rawTitle;
          item.addEventListener("click", () => {
            searchInput.value = match.rawTitle;
            executeSearch(match.rawTitle);
          });
          searchDropdown.appendChild(item);
        });

        const goBtn = document.createElement("button");
        goBtn.className = "comic-btn";
        goBtn.style.width = "100%";
        goBtn.style.borderRadius = "0 0 16px 16px";
        goBtn.style.padding = "12px";
        goBtn.style.border = "none";
        goBtn.style.borderTop = "var(--border-width) solid var(--text-color)";
        goBtn.innerHTML = 'Go There <i class="fa-solid fa-arrow-right"></i>';
        goBtn.addEventListener("click", () => executeSearch(query));
        searchDropdown.appendChild(goBtn);

        searchDropdown.style.display = "block";
      } else {
        searchDropdown.innerHTML =
          '<div style="padding:15px; font-weight:700;">No results found.</div>';
        searchDropdown.style.display = "block";
      }
    });

    // Hide dropdown when clicking outside
    document.addEventListener("click", (e) => {
      if (!searchContainer.contains(e.target)) {
        searchDropdown.style.display = "none";
      }
    });

    // Handle Enter key
    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        executeSearch(searchInput.value);
      }
    });

    function executeSearch(query) {
      searchDropdown.style.display = "none";
      if (query.trim() && typeof gtag !== 'undefined') gtag('event', 'search_performed', { search_term: query.trim() });
      window.location.href = "gallery.php?search=" + encodeURIComponent(query);
    }
  }

  // ===========================================================
  // SAVE PROMPT — unified click handler (modal SAVE button only)
  // Unsave is intentionally NOT handled here; it lives only on
  // the Saved Prompts page (saved_prompts.php).
  // ===========================================================
  document.addEventListener("click", function (e) {
    const saveBtn = e.target.closest(".save-prompt-btn");
    if (!saveBtn) return;
    if (saveBtn.disabled || saveBtn.dataset.saved === "true") return;

    const promptId = saveBtn.dataset.promptId;
    if (!promptId) return;

    if (typeof isLoggedIn !== "undefined" && !isLoggedIn) {
      const popup = document.getElementById("login-save-popup");
      if (popup) popup.style.display = "flex";
      return;
    }

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

    const fd = new FormData();
    fd.append("action", "save");
    fd.append("prompt_id", promptId);

    fetch("save_prompt.php", { method: "POST", body: fd })
      .then((r) => r.json())
      .then((data) => {
        if (data.success && data.saved) {
          if (typeof gtag !== 'undefined') gtag('event', 'prompt_save', { prompt_id: promptId });
          applySaveBtnState(saveBtn, true);
          // Reflect on the originating card so subsequent modal opens
          // see the saved state without a refresh.
          if (currentCardElement) {
            currentCardElement.dataset.saved = "true";
          }
          const card = document.querySelector(
            '.card[data-id="' + promptId + '"]',
          );
          if (card) card.dataset.saved = "true";
        } else {
          applySaveBtnState(saveBtn, false);
          showComicAlert(data.message || "Could not save prompt.", "error");
        }
      })
      .catch(() => {
        applySaveBtnState(saveBtn, false);
        showComicAlert("Network error. Try again.", "error");
      });
  });
});

/* =========================================================
   BACK TO TOP BUTTON
   ========================================================= */
(function () {
  if (document.getElementById("back-to-top")) return;
  var btn = document.createElement("button");
  btn.type = "button";
  btn.id = "back-to-top";
  btn.setAttribute("aria-label", "Back to top");
  btn.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
  document.body.appendChild(btn);

  window.addEventListener(
    "scroll",
    function () {
      btn.classList.toggle("visible", window.scrollY > 400);
    },
    { passive: true },
  );

  btn.addEventListener("click", function () {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
})();

/* =========================================================
   FIRST UNLOCK CELEBRATION (shows only once ever)
   ========================================================= */
function checkFirstUnlock() {
  if (!localStorage.getItem("pv_first_unlock")) {
    localStorage.setItem("pv_first_unlock", "1");

    // Toast notification
    var toast = document.createElement("div");
    toast.className = "first-unlock-toast";
    toast.innerHTML = "🎉 First Prompt Unlocked! Welcome to the Verse!";
    document.body.appendChild(toast);
    setTimeout(function () {
      toast.classList.add("show");
    }, 100);
    setTimeout(function () {
      toast.classList.remove("show");
      setTimeout(function () {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 500);
    }, 3800);

    // Extra emoji rain
    if (typeof triggerEmojiRain === "function") {
      setTimeout(function () {
        triggerEmojiRain("🎉", 12);
      }, 200);
      setTimeout(function () {
        triggerEmojiRain("✨", 10);
      }, 600);
    }
  }
}

/* =========================================================
   PROFILE MODAL (Centered for both Mobile & Desktop)
   ========================================================= */
(function () {
  if (typeof isLoggedIn === "undefined" || !isLoggedIn) return;

  var headerRight = document.querySelector(".header-right");
  if (!headerRight) return;

  var profileLink = headerRight.querySelector('a[href="profile.php"]');
  if (!profileLink) return;

  // Make the avatar non-navigating (we handle click ourselves)
  profileLink.addEventListener("click", function (e) {
    e.preventDefault();
    openModal();
  });

  /* ── Build the menu content ── */
  function buildMenuContent(container) {
    container.innerHTML = "";

    // ── ADMIN label (only for admins) ──
    if (typeof isAdmin !== "undefined" && isAdmin) {
      var adminRow = document.createElement("a");
      adminRow.href = "dashboard.php";
      adminRow.style.cssText =
        "display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--text-color,#2d2a35);text-decoration:none;font-weight:900;font-size:.88rem;background:var(--primary-color,#c8b4f8);border-bottom:1px solid var(--border-color,#eae3f2);letter-spacing:.5px;";
      adminRow.innerHTML =
        '<i class="fa-solid fa-shield-halved" style="width:16px;text-align:center;"></i> ADMIN DASHBOARD';
      container.appendChild(adminRow);
    }

    // ── Streak row (hidden until data loads) ──
    var streakRow = document.createElement("div");
    streakRow.style.cssText =
      "display:none;align-items:center;gap:8px;padding:12px 16px;background:#fff8e0;border-bottom:1px solid var(--border-color,#eae3f2);font-weight:800;font-size:.88rem;color:#7a5800;";
    streakRow.id = "modal-streak-row";
    container.appendChild(streakRow);

    // ── Menu links ──
    var links = [
      { href: "profile.php", icon: "fa-solid fa-user", label: "Edit Profile" },
      { href: "saved_prompts.php", icon: "fa-solid fa-bookmark", label: "Saved Prompts" },
    ];
    links.forEach(function (l) {
      var a = document.createElement("a");
      a.href = l.href;
      a.style.cssText =
        "display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--text-color,#2d2a35);text-decoration:none;font-weight:700;font-size:.88rem;transition:background .15s;";
      a.innerHTML =
        '<i class="' + l.icon + '" style="width:16px;text-align:center;"></i> ' + l.label;
      a.addEventListener("mouseover", function () { this.style.background = "var(--bg-color,#fdfbf7)"; });
      a.addEventListener("mouseout", function () { this.style.background = ""; });
      container.appendChild(a);
    });

    // ── Divider ──
    var divider = document.createElement("div");
    divider.style.cssText = "height:1px;background:var(--border-color,#eae3f2);margin:4px 0;";
    container.appendChild(divider);

    // ── Logout ──
    var logout = document.createElement("a");
    logout.href = "login.php?logout=1";
    logout.style.cssText =
      "display:flex;align-items:center;gap:10px;padding:12px 16px;color:#d03030;text-decoration:none;font-weight:700;font-size:.88rem;transition:background .15s;";
    logout.innerHTML =
      '<i class="fa-solid fa-right-from-bracket" style="width:16px;text-align:center;"></i> Logout';
    logout.addEventListener("mouseover", function () { this.style.background = "#fff5f5"; });
    logout.addEventListener("mouseout", function () { this.style.background = ""; });
    container.appendChild(logout);

    return streakRow;
  }

  /* ══════════════════════════════════════════════
     MODAL OVERLAY
  ══════════════════════════════════════════════ */
  var overlay = document.createElement("div");
  overlay.id = "profile-centered-modal";
  overlay.style.cssText = [
    "display:none",
    "position:fixed",
    "inset:0",
    "z-index:9999",
    "background:rgba(0,0,0,0.55)",
    "align-items:center",
    "justify-content:center",
    "backdrop-filter:blur(4px)",
    "-webkit-backdrop-filter:blur(4px)",
  ].join(";");

  var modalBox = document.createElement("div");
  modalBox.style.cssText = [
    "position:relative",
    "background:var(--card-bg,#fff)",
    "border:var(--border-width,3px) solid var(--text-color,#2d2a35)",
    "border-radius:22px",
    "box-shadow:6px 6px 0 var(--text-color,#2d2a35)",
    "min-width:260px",
    "max-width:320px",
    "width:88vw",
    "overflow:hidden",
    "font-family:var(--font-main,Outfit,sans-serif)",
    "animation:modalPopIn .2s cubic-bezier(.34,1.56,.64,1) both",
  ].join(";");

  // ✕ close button
  var closeBtn = document.createElement("button");
  closeBtn.innerHTML = "✕";
  closeBtn.style.cssText = [
    "position:absolute",
    "top:10px",
    "right:12px",
    "background:none",
    "border:none",
    "font-size:1.1rem",
    "font-weight:900",
    "color:var(--text-color,#2d2a35)",
    "cursor:pointer",
    "z-index:10",
    "line-height:1",
    "padding:4px 8px",
    "border-radius:8px",
    "transition:background .15s",
  ].join(";");
  closeBtn.onmouseover = function() { this.style.background = "var(--bg-color,#fdfbf7)"; };
  closeBtn.onmouseout = function() { this.style.background = ""; };

  // Modal title row
  var modalTitle = document.createElement("div");
  modalTitle.style.cssText =
    "padding:14px 16px 10px;font-weight:900;font-size:.8rem;text-transform:uppercase;letter-spacing:1px;color:#aaa;border-bottom:1px solid var(--border-color,#eae3f2);";
  modalTitle.textContent = "My Account";

  var modalContent = document.createElement("div");
  var modalStreak = buildMenuContent(modalContent);

  modalBox.appendChild(closeBtn);
  modalBox.appendChild(modalTitle);
  modalBox.appendChild(modalContent);
  overlay.appendChild(modalBox);
  document.body.appendChild(overlay);

  // Add modal pop-in animation if it doesn't exist
  if (!document.getElementById("modal-pop-style")) {
    var modalStyle = document.createElement("style");
    modalStyle.id = "modal-pop-style";
    modalStyle.textContent = "@keyframes modalPopIn{from{opacity:0;transform:scale(.88) translateY(16px)}to{opacity:1;transform:scale(1) translateY(0)}}";
    document.head.appendChild(modalStyle);
  }

  /* ── Toggle logic ── */
  function openModal() {
    overlay.style.display = "flex";
    modalBox.style.animation = "none";
    requestAnimationFrame(function() {
      modalBox.style.animation = "modalPopIn .2s cubic-bezier(.34,1.56,.64,1) both";
    });
  }
  function closeModal() {
    overlay.style.display = "none";
  }

  closeBtn.addEventListener("click", closeModal);
  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) closeModal();
  });
  document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") closeModal();
  });

  /* ── Fetch streak + new prompts ── */
  fetch("user_data.php")
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.streak >= 1) {
        modalStreak.innerHTML = '<span style="font-size:1.1rem;">🔥</span><span>' + data.streak + " Day Streak!</span>";
        modalStreak.style.display = "flex";
      }
      // NEW dot on avatar
      if (data.new_prompts > 0) {
        var dot = document.createElement("span");
        dot.style.cssText =
          "position:absolute;top:-2px;right:-2px;width:11px;height:11px;background:#FF4444;border:2px solid var(--card-bg,#fff);border-radius:50%;animation:pulse-dot 1.5s infinite;";
        dot.title = data.new_prompts + " new prompt(s) this week!";
        profileLink.style.position = "relative";
        profileLink.style.display = "inline-flex";
        profileLink.appendChild(dot);
      }
    })
    .catch(function () {});
})();

// ── Scroll Depth Tracking ──
(function () {
  if (typeof gtag === 'undefined') return;
  const depths = [25, 50, 75, 90];
  const fired = new Set();
  window.addEventListener('scroll', function () {
    const scrolled = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight * 100;
    depths.forEach(function (d) {
      if (scrolled >= d && !fired.has(d)) {
        fired.add(d);
        gtag('event', 'scroll_depth', { depth_percent: d, page: window.location.pathname });
      }
    });
  }, { passive: true });
})();


/* =========================================
   UX IMPROVEMENTS: TOASTS & VALIDATION
   ========================================= */

// 1. Toast Notification System
function showToast(message, type = "success") {
    let container = document.getElementById("toast-container");
    if (!container) {
        container = document.createElement("div");
        container.id = "toast-container";
        document.body.appendChild(container);
    }
    
    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    
    // Icon based on type
    let icon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
    if (type === "error") {
        icon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
    } else if (type === "info") {
        icon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
    }
    
    toast.innerHTML = `${icon} <span>${message}</span>`;
    container.appendChild(toast);
    
    // Animate in
    setTimeout(() => { toast.classList.add("show"); }, 10);
    
    // Animate out and remove after 3s
    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// 2. Client-Side Form Validation
document.addEventListener("DOMContentLoaded", () => {
    const forms = document.querySelectorAll(".needs-validation");
    forms.forEach(form => {
        form.addEventListener("submit", function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                showToast("Please fill in all required fields correctly.", "error");
            }
            form.classList.add("was-validated"); // Optional: for bootstrap-like css styling if needed
        }, false);
    });
});

/* =========================================
   UX IMPROVEMENTS: AJAX PAGINATION
   ========================================= */
document.addEventListener("DOMContentLoaded", () => {
    // Intercept pagination clicks in gallery
    document.body.addEventListener("click", function(e) {
        const pageLink = e.target.closest(".pagination a");
        if (pageLink && !pageLink.closest(".no-ajax")) {
            e.preventDefault();
            const url = new URL(pageLink.href);
            url.searchParams.set("ajax", "1");
            
            // Show skeletons in the grid
            const grid = document.querySelector(".gallery-grid");
            if (grid) {
                // Keep the same number of items, just turn them to skeletons
                const cards = grid.querySelectorAll(".prompt-card");
                cards.forEach(card => {
                    const img = card.querySelector(".prompt-image");
                    if (img) {
                        img.innerHTML = "<div class=\"skeleton\" style=\"width:100%;height:100%;min-height:200px;\"></div>";
                    }
                    card.style.pointerEvents = "none";
                    card.style.opacity = "0.7";
                });
                
                // Scroll to top smoothly
                window.scrollTo({ top: 0, behavior: "smooth" });
                
                fetch(url.toString(), {
                    headers: { "X-Requested-With": "XMLHttpRequest" }
                })
                .then(res => res.text())
                .then(html => {
                    // Expecting HTML fragment containing the new cards and new pagination
                    // Parse the HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, "text/html");
                    
                    const newGrid = doc.querySelector(".gallery-grid");
                    const newPagination = doc.querySelector(".pagination");
                    
                    if (newGrid) {
                        grid.innerHTML = newGrid.innerHTML;
                    }
                    const currentPagination = document.querySelector(".pagination");
                    if (currentPagination && newPagination) {
                        currentPagination.innerHTML = newPagination.innerHTML;
                    }
                    
                    // Update URL without reload
                    url.searchParams.delete("ajax");
                    window.history.pushState({path: url.href}, "", url.href);
                })
                .catch(err => {
                    console.error(err);
                    showToast("Failed to load next page.", "error");
                    // Fallback to hard load
                    window.location.href = pageLink.href;
                });
            }
        }
    });
});



/* =========================================
   CONVERT EXISTING FLASH MESSAGES TO TOASTS
   ========================================= */
document.addEventListener("DOMContentLoaded", () => {
    const flashMessages = document.querySelectorAll(".flash, .alert");
    flashMessages.forEach(msg => {
        // Determine type
        let type = "info";
        if (msg.classList.contains("flash-ok") || msg.classList.contains("alert-success")) {
            type = "success";
        } else if (msg.classList.contains("flash-err") || msg.classList.contains("flash-error") || msg.classList.contains("alert-error") || msg.classList.contains("alert-danger")) {
            type = "error";
        }
        
        // Get text content and strip HTML tags if any (like <i>)
        const text = msg.textContent.trim();
        
        if (text) {
            showToast(text, type);
        }
        
        // Hide original element
        msg.style.display = "none";
    });
});

