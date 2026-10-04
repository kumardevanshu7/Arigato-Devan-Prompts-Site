<?php
$_gtag_script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
if (!isset($_page_canonical)) {
    $_page_canonical = 'https://arigatodevan.com' . strtok($_gtag_script, '?');
}
?>
<!-- Canonical URL -->
<link rel="canonical" href="<?= htmlspecialchars($_page_canonical) ?>">
<!-- Organization & WebSite Schema — appears on all pages -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebSite",
      "@id": "https://arigatodevan.com/#website",
      "name": "Arigato Devan Prompts",
      "url": "https://arigatodevan.com",
      "description": "Premium AI couple prompts for Instagram Reels. Unlock secret, viral and unreleased prompts on Arigato Devan.",
      "publisher": {
        "@id": "https://arigatodevan.com/#organization"
      }
    },
    {
      "@type": "Organization",
      "@id": "https://arigatodevan.com/#organization",
      "name": "Arigato Devan",
      "url": "https://arigatodevan.com",
      "logo": "https://arigatodevan.com/toplogo/logo01.webp",
      "sameAs": [
        "https://www.instagram.com/arigato.devan/"
      ],
      "contactPoint": {
        "@type": "ContactPoint",
        "contactType": "customer support",
        "url": "https://arigatodevan.com/contact.php"
      }
    }
  ]
}
</script>
<!-- Font Awesome — high priority preload (LCP fix: FA icons in navbar) -->
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" fetchpriority="high" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
<!-- Google Fonts — non-blocking (preconnect + preload swap) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap"></noscript>
<!-- Favicons -->
<link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
<link rel="icon" type="image/png" sizes="48x48" href="/favicon/favicon-48x48.png">
<link rel="icon" type="image/png" sizes="192x192" href="/favicon/android-chrome-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="/favicon/android-chrome-512x512.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
<link rel="manifest" href="/favicon/site.webmanifest">
<meta name="theme-color" content="#e6d7ff">
<!-- Google tag (gtag.js) — G-1B4V97JP7T -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-1B4V97JP7T"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-1B4V97JP7T');
  // Instagram in-app browser detection — fix Unassigned traffic
  (function(){
    var ua = navigator.userAgent || '';
    var isInsta = ua.indexOf('Instagram') > -1;
    var isFB    = ua.indexOf('FBAN') > -1 || ua.indexOf('FBAV') > -1;
    if (isInsta || isFB) {
      gtag('event', 'instagram_inapp_visit', {
        'traffic_source' : isInsta ? 'instagram' : 'facebook',
        'page_path'      : window.location.pathname
      });
    }
  })();
</script>
<?php /* FCM disabled temporarily */ ?>
<?php // if (file_exists(__DIR__ . '/fcm_init.php')) include_once __DIR__ . '/fcm_init.php'; ?>
<!-- ── Open-in-Browser banner (Instagram / FB in-app) ── -->
<style>
#inapp-banner {
  display: none;
  position: fixed;
  top: 14px;
  left: 14px;
  right: 14px;
  max-width: 580px;
  margin: 0 auto;
  z-index: 999999;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(86, 124, 141, 0.22);
  border-radius: 22px;
  padding: 12px 16px;
  box-shadow: 0 14px 40px -10px rgba(15, 23, 42, 0.18), 0 4px 12px rgba(15, 23, 42, 0.06);
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  animation: inappSlideDown 0.32s cubic-bezier(0.16, 1, 0.3, 1);
  box-sizing: border-box;
}
@keyframes inappSlideDown {
  from { transform: translateY(-24px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
#inapp-banner .ib-icon {
  width: 42px;
  height: 42px;
  background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
  border: none;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: #ffffff;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(220, 39, 67, 0.32);
}
#inapp-banner .ib-text {
  flex: 1;
  min-width: 150px;
}
#inapp-banner .ib-title {
  font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
  font-size: 0.92rem;
  font-weight: 800;
  color: #204162;
  line-height: 1.25;
}
#inapp-banner .ib-sub {
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 0.78rem;
  font-weight: 500;
  color: #567C8D;
  margin-top: 2px;
}
#inapp-banner .ib-btns {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
}
.ib-btn {
  padding: 8px 16px;
  border-radius: 999px;
  font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
  font-weight: 700;
  font-size: 0.82rem;
  cursor: pointer;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
  border: none;
  text-decoration: none;
}
.ib-open {
  background: #204162;
  color: #ffffff;
  box-shadow: 0 4px 14px rgba(32, 65, 98, 0.22);
}
.ib-open:hover {
  background: #152c42;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(32, 65, 98, 0.32);
}
.ib-copy {
  background: rgba(200, 217, 230, 0.4);
  color: #204162;
  border: 1px solid rgba(86, 124, 141, 0.25);
}
.ib-copy:hover {
  background: rgba(200, 217, 230, 0.65);
  transform: translateY(-1px);
}
#ib-close-btn {
  background: #f1f5f9;
  border: none;
  font-size: 0.95rem;
  cursor: pointer;
  color: #64748b;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  flex-shrink: 0;
  transition: all 0.2s ease;
}
#ib-close-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}
</style>
<div id="inapp-banner" class="google-anno-skip" role="alert">
  <div class="ib-icon"><i class="fa-brands fa-instagram"></i></div>
  <div class="ib-text">
    <div class="ib-title">Instagram ka browser hai</div>
    <div class="ib-sub">Smooth prompts aur downloads ke liye Chrome / Safari mein kholein</div>
  </div>
  <div class="ib-btns">
    <button class="ib-btn ib-open" id="ib-open-btn"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open in Browser</button>
    <button class="ib-btn ib-copy" id="ib-copy-btn"><i class="fa-solid fa-copy"></i> Copy Link</button>
  </div>
  <button id="ib-close-btn" onclick="document.getElementById('inapp-banner').style.display='none'" aria-label="Close banner"><i class="fa-solid fa-xmark"></i></button>
</div>
<script>
(function(){
  var ua = navigator.userAgent || '';
  var isInsta = ua.indexOf('Instagram') > -1;
  var isFB    = ua.indexOf('FBAN') > -1 || ua.indexOf('FBAV') > -1;
  if (!(isInsta || isFB)) return;
  document.getElementById('inapp-banner').style.display = 'flex';
  var url = window.location.href;
  var isAndroid = /android/i.test(ua);
  var isIOS = /iphone|ipad|ipod/i.test(ua);

  document.getElementById('ib-open-btn').addEventListener('click', function(){
    if (isAndroid) {
      // Try Chrome first, fallback to default browser
      var intentUrl = 'intent://' + url.replace(/^https?:\/\//, '') + '#Intent;scheme=https;package=com.android.chrome;S.browser_fallback_url=' + encodeURIComponent(url) + ';end';
      window.location.href = intentUrl;
    } else if (isIOS) {
      // iOS: try Chrome app, else copy + instruct
      var chromeUrl = url.replace(/^https?:\/\//, 'googlechrome://');
      var tryChrome = window.open(chromeUrl);
      setTimeout(function(){
        if (!tryChrome || tryChrome.closed) {
          navigator.clipboard.writeText(url).catch(function(){});
          alert('Link copy ho gaya! Safari address bar mein paste karke kholen.');
        }
      }, 800);
    } else {
      window.open(url, '_blank');
    }
  });

  document.getElementById('ib-copy-btn').addEventListener('click', function(){
    var btn = document.getElementById('ib-copy-btn');
    navigator.clipboard.writeText(url).then(function(){
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
      btn.style.background = '#e6fffa';
      btn.style.color = '#0d9488';
      btn.style.borderColor = '#5eead4';
      setTimeout(function(){
        btn.innerHTML = '<i class="fa-solid fa-copy"></i> Copy Link';
        btn.style.background = '';
        btn.style.color = '';
        btn.style.borderColor = '';
      }, 2500);
    }).catch(function(){ window.prompt('Copy karo:', url); });
  });
})();
</script>
<?php /* Custom "Nagao arrow" site cursor — disabled site-wide per user request. Files kept in css/site-cursor.css and js/site-cursor.js in case it's ever wanted back. */ ?>
<style>#sound-toggle-btn{display:none!important;}</style>
<script>
/* ── Unlock sound (callable globally) ── */
window.playUnlockSound=function(){
  if(localStorage.getItem('arigatoSound')==='off') return;
  try{
    var ctx=new(window.AudioContext||window.webkitAudioContext)();
    function note(f,t,d){
      var o=ctx.createOscillator(),g=ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type='sine'; o.frequency.value=f;
      g.gain.setValueAtTime(.22,ctx.currentTime+t);
      g.gain.exponentialRampToValueAtTime(.001,ctx.currentTime+t+d);
      o.start(ctx.currentTime+t); o.stop(ctx.currentTime+t+d+.05);
    }
    note(523,0,.12); note(659,.1,.12); note(784,.18,.15); note(1047,.28,.35);
  }catch(e){}
};

/* ── Activity ping (last_active tracking) ── */
<?php if (isset($_SESSION['user_id'])): ?>
(function(){ fetch('activity_ping.php', {method:'POST', keepalive:true}); })();
<?php endif; ?>

/* ── Auto-detect prompt unlock on any page ── */
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('#modal-unlocked-area,.modal-unlocked-area').forEach(function(el){
    new MutationObserver(function(){
      if(el.style.display&&el.style.display!=='none') window.playUnlockSound();
    }).observe(el,{attributes:true,attributeFilter:['style']});
  });
});
</script>
