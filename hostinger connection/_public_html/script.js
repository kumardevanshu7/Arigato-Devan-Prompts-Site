document.addEventListener("DOMContentLoaded", () => {
  const cards = document.querySelectorAll(".card");
  const modalOverlay = document.getElementById("unlock-modal");
  const closeModalBtn = document.querySelector(".close-modal");
  const submitCodeBtn = document.getElementById("submit-code");
  const codeInput = document.getElementById("unlock-code-input");

  let currentPromptId = null;
  let currentCardElement = null;

  // Custom Alert System — only shows errors, not success/debug toasts
  function showComicAlert(message, type = "error") {
    // Disabled per user request
    return;
  }

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
        e.target.closest(".card-category-pill")
      ) {
        e.stopPropagation();
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
      if (saveBtn) {
        saveBtn.dataset.promptId = card.dataset.id;
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fa-solid fa-bookmark"></i> SAVE';
        saveBtn.style.background = "";
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
          if (card.dataset.reel) {
            if (modalReelLink) modalReelLink.href = card.dataset.reel;
            if (wantCodeSection) wantCodeSection.style.display = "block";
          }
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
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-bookmark"></i> SAVE';
            saveBtn.style.background = "";
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
    if (e.target.closest(".copy-btn")) {
      const btn = e.target.closest(".copy-btn");
      // Try modal first, then closest unlocked-state
      let textToCopy = "";
      const modalText = document.getElementById("modal-unlocked-text");
      if (modalText && modalText.textContent) {
        textToCopy = modalText.textContent;
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
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-bookmark"></i> SAVE';
            saveBtn.style.background = "";
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

  function unlockAlreadyUploaded(promptId) {
    const formData = new FormData();
    formData.append("action", "already_uploaded");
    formData.append("prompt_id", promptId);

    fetch("unlock.php", { method: "POST", body: formData })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
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

          // Check if already saved
          if (saveBtn) {
            const formDataCheck = new FormData();
            formDataCheck.append("action", "check");
            formDataCheck.append("prompt_id", promptId);
            fetch("like.php", { method: "POST", body: formDataCheck })
              .then((r) => r.json())
              .then((d) => {
                if (d.saved) {
                  saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> SAVED';
                  saveBtn.style.background = "#4CAF50";
                  saveBtn.style.color = "white";
                }
              });
          }
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
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-bookmark"></i> SAVE';
            saveBtn.style.background = "";
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
          // First unlock celebration
          if (typeof checkFirstUnlock === "function") checkFirstUnlock();
        } else {
          showComicAlert(data.message || "Failed to unlock! Try again.", "error");
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
      window.location.href = "gallery.php?search=" + encodeURIComponent(query);
    }
  }
});

/* =========================================================
   BACK TO TOP BUTTON
   ========================================================= */
(function () {
  var btn = document.createElement("button");
  btn.id = "back-to-top";
  btn.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
  btn.title = "Back to top";
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
   PROFILE DROPDOWN  +  STREAK BADGE  +  NEW BADGE
   ========================================================= */
(function () {
  if (typeof isLoggedIn === "undefined" || !isLoggedIn) return;

  var headerRight = document.querySelector(".header-right");
  if (!headerRight) return;

  var profileLink = headerRight.querySelector('a[href="profile.php"]');
  if (!profileLink) return;

  // Wrap profileLink in a relative-positioned container
  var wrap = document.createElement("div");
  wrap.className = "profile-dd-wrap";
  wrap.style.cssText = "position:relative;display:inline-block;cursor:pointer;";
  profileLink.parentNode.insertBefore(wrap, profileLink);
  wrap.appendChild(profileLink);

  // Make the avatar non-navigating (we handle click ourselves)
  profileLink.addEventListener("click", function (e) {
    e.preventDefault();
  });

  // Build dropdown menu
  var menu = document.createElement("div");
  menu.className = "profile-dd-menu";
  menu.style.cssText = [
    "position:absolute",
    "top:calc(100% + 10px)",
    "right:0",
    "min-width:200px",
    "background:var(--card-bg,#fff)",
    "border:var(--border-width,3px) solid var(--text-color,#2d2a35)",
    "border-radius:18px",
    "box-shadow:5px 5px 0 var(--text-color,#2d2a35)",
    "overflow:hidden",
    "z-index:3000",
    "opacity:0",
    "transform:translateY(-8px) scale(.97)",
    "pointer-events:none",
    "transition:opacity .2s,transform .2s",
    "font-family:var(--font-main,Outfit,sans-serif)",
  ].join(";");

  // Streak row (hidden until data loads)
  var streakRow = document.createElement("div");
  streakRow.style.cssText =
    "display:none;align-items:center;gap:8px;padding:12px 16px;background:#fff8e0;border-bottom:1px solid var(--border-color,#eae3f2);font-weight:800;font-size:.88rem;color:#7a5800;";
  streakRow.id = "dd-streak-row";
  menu.appendChild(streakRow);

  // Menu links
  var links = [
    { href: "profile.php", icon: "fa-solid fa-user", label: "Edit Profile" },
    {
      href: "saved_prompts.php",
      icon: "fa-solid fa-bookmark",
      label: "Saved Prompts",
    },
  ];
  links.forEach(function (l) {
    var a = document.createElement("a");
    a.href = l.href;
    a.style.cssText =
      "display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--text-color,#2d2a35);text-decoration:none;font-weight:700;font-size:.88rem;transition:background .15s;";
    a.innerHTML =
      '<i class="' +
      l.icon +
      '" style="width:16px;text-align:center;"></i> ' +
      l.label;
    a.addEventListener("mouseover", function () {
      this.style.background = "var(--bg-color,#fdfbf7)";
    });
    a.addEventListener("mouseout", function () {
      this.style.background = "";
    });
    menu.appendChild(a);
  });

  // Divider
  var divider = document.createElement("div");
  divider.style.cssText =
    "height:1px;background:var(--border-color,#eae3f2);margin:4px 0;";
  menu.appendChild(divider);

  // Logout
  var logout = document.createElement("a");
  logout.href = "login.php?logout=1";
  logout.style.cssText =
    "display:flex;align-items:center;gap:10px;padding:12px 16px;color:#d03030;text-decoration:none;font-weight:700;font-size:.88rem;transition:background .15s;";
  logout.innerHTML =
    '<i class="fa-solid fa-right-from-bracket" style="width:16px;text-align:center;"></i> Logout';
  logout.addEventListener("mouseover", function () {
    this.style.background = "#fff5f5";
  });
  logout.addEventListener("mouseout", function () {
    this.style.background = "";
  });
  menu.appendChild(logout);

  wrap.appendChild(menu);

  // Toggle open/close
  function openDD() {
    menu.style.opacity = "1";
    menu.style.transform = "translateY(0) scale(1)";
    menu.style.pointerEvents = "all";
    wrap.classList.add("open");
  }
  function closeDD() {
    menu.style.opacity = "0";
    menu.style.transform = "translateY(-8px) scale(.97)";
    menu.style.pointerEvents = "none";
    wrap.classList.remove("open");
  }

  wrap.addEventListener("click", function (e) {
    e.stopPropagation();
    wrap.classList.contains("open") ? closeDD() : openDD();
  });
  document.addEventListener("click", function (e) {
    if (!wrap.contains(e.target)) closeDD();
  });

  // Fetch streak + new prompts
  fetch("user_data.php")
    .then(function (r) {
      return r.json();
    })
    .then(function (data) {
      // Streak inside dropdown
      if (data.streak >= 1) {
        streakRow.innerHTML =
          '<span style="font-size:1.1rem;">🔥</span><span>' +
          data.streak +
          " Day Streak!</span>";
        streakRow.style.display = "flex";
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
