const fs = require('fs');
let content = fs.readFileSync('script.js', 'utf8');

let startIndex = content.indexOf('/* =========================================================\r\n     PROFILE DROPDOWN');
if (startIndex === -1) startIndex = content.indexOf('/* =========================================================\n     PROFILE DROPDOWN');

let endIndex = content.indexOf('// "?"? Scroll Depth Tracking');
if (endIndex === -1) endIndex = content.indexOf('// ✨ Scroll Depth Tracking');

if (startIndex === -1 || endIndex === -1) {
    console.error("Could not find start or end index.");
    process.exit(1);
}

const newIIFE = `/* =========================================================
     PROFILE MODAL (Centered for both Mobile & Desktop)
     ========================================================= */
  (function () {
    if (typeof isLoggedIn === "undefined" || !isLoggedIn) return;

    var headerRight = document.querySelector(".header-right");
    if (!headerRight) return;

    var profileLink = headerRight.querySelector('a[href="profile.php"]');
    if (!profileLink) return;

    // Make the avatar non-navigating
    profileLink.addEventListener("click", function (e) {
      e.preventDefault();
      openModal();
    });

    /* ✨ Build the menu content ✨ */
    function buildMenuContent(container) {
      container.innerHTML = "";

      // ✨ ADMIN label (only for admins) ✨
      if (typeof isAdmin !== "undefined" && isAdmin) {
        var adminRow = document.createElement("a");
        adminRow.href = "dashboard.php";
        adminRow.style.cssText =
          "display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--text-color,#2d2a35);text-decoration:none;font-weight:900;font-size:.88rem;background:var(--primary-color,#c8b4f8);border-bottom:1px solid var(--border-color,#eae3f2);letter-spacing:.5px;";
        adminRow.innerHTML =
          '<i class="fa-solid fa-shield-halved" style="width:16px;text-align:center;"></i> ADMIN DASHBOARD';
        container.appendChild(adminRow);
      }

      // ✨ Streak row (hidden until data loads) ✨
      var streakRow = document.createElement("div");
      streakRow.style.cssText =
        "display:none;align-items:center;gap:8px;padding:12px 16px;background:#fff8e0;border-bottom:1px solid var(--border-color,#eae3f2);font-weight:800;font-size:.88rem;color:#7a5800;";
      streakRow.id = "modal-streak-row";
      container.appendChild(streakRow);

      // ✨ Menu links ✨
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

      // ✨ Divider ✨
      var divider = document.createElement("div");
      divider.style.cssText = "height:1px;background:var(--border-color,#eae3f2);margin:4px 0;";
      container.appendChild(divider);

      // ✨ Logout ✨
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

    /* ✨ MODAL OVERLAY ✨ */
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

    var closeBtn = document.createElement("button");
    closeBtn.innerHTML = "✖";
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

    if (!document.getElementById("modal-pop-style")) {
      var modalStyle = document.createElement("style");
      modalStyle.id = "modal-pop-style";
      modalStyle.textContent = "@keyframes modalPopIn{from{opacity:0;transform:scale(.88) translateY(16px)}to{opacity:1;transform:scale(1) translateY(0)}}";
      document.head.appendChild(modalStyle);
    }

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

    /* ✨ Fetch streak + new prompts ✨ */
    fetch("user_data.php")
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.streak >= 1) {
          modalStreak.innerHTML = '<span style="font-size:1.1rem;">🔥</span><span>' + data.streak + " Day Streak!</span>";
          modalStreak.style.display = "flex";
        }
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

`;

content = content.substring(0, startIndex) + newIIFE + content.substring(endIndex);
fs.writeFileSync('script.js', content, 'utf8');
console.log("Success");
