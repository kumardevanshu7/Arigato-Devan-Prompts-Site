<?php
$_gtag_script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
if (!isset($_page_canonical)) {
    $_page_canonical = 'https://arigatodevan.com' . strtok($_gtag_script, '?');
}
?>
<!-- Canonical URL -->
<link rel="canonical" href="<?= htmlspecialchars($_page_canonical) ?>">
<!-- Preload background wallpaper images (LCP/FCP boost) -->
<link rel="preload" as="image" href="/backgroundwally/phone-wally.webp" media="(max-width: 768px)">
<link rel="preload" as="image" href="/backgroundwally/laptop-wally.webp" media="(min-width: 769px)">
<!-- Organization Schema — appears on all pages -->
<script type="application/ld+json">
[
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Arigato Devan Prompts",
    "url": "https://arigatodevan.com",
    "logo": "https://arigatodevan.com/favicon/android-chrome-512x512.png",
    "sameAs": ["https://www.instagram.com/arigato.devan/"]
  },
  {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "Arigato Devan Prompts",
    "url": "https://arigatodevan.com",
    "potentialAction": {
      "@type": "SearchAction",
      "target": "https://arigatodevan.com/gallery.php?q={search_term_string}",
      "query-input": "required name=search_term_string"
    }
  }
]
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
#inapp-banner{display:none;position:fixed;bottom:16px;left:12px;right:12px;z-index:99999;background:var(--card-bg,#fff);border:3px solid #2d2a35;border-radius:20px;padding:16px 18px;font-family:'Outfit',sans-serif;box-shadow:5px 5px 0 #2d2a35;align-items:center;gap:14px;flex-wrap:wrap;}
#inapp-banner .ib-icon{width:42px;height:42px;background:#ffe3fb;border:2.5px solid #2d2a35;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#7c3aed;flex-shrink:0;}
#inapp-banner .ib-text{flex:1;min-width:160px;}
#inapp-banner .ib-title{font-size:.95rem;font-weight:900;color:#2d2a35;line-height:1.2;}
#inapp-banner .ib-sub{font-size:.78rem;font-weight:600;color:#7D7887;margin-top:2px;}
#inapp-banner .ib-btns{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.ib-btn{padding:9px 16px;border-radius:10px;font-family:'Outfit',sans-serif;font-weight:800;font-size:.82rem;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;transition:all .15s;}
.ib-open{background:#c084fc;color:#2d2a35;border:2.5px solid #2d2a35;box-shadow:3px 3px 0 #2d2a35;}
.ib-open:hover{transform:translateY(-1px);box-shadow:4px 4px 0 #2d2a35;}
.ib-copy{background:var(--bg-color,#f8f4ff);color:#2d2a35;border:2.5px solid #2d2a35;box-shadow:3px 3px 0 #2d2a35;}
.ib-copy:hover{transform:translateY(-1px);box-shadow:4px 4px 0 #2d2a35;}
#ib-close-btn{background:none;border:none;font-size:1rem;cursor:pointer;color:#aaa;padding:4px;line-height:1;flex-shrink:0;}
</style>
<div id="inapp-banner">
  <div class="ib-icon"><i class="fa-brands fa-instagram"></i></div>
  <div class="ib-text">
    <div class="ib-title">Instagram ka browser hai</div>
    <div class="ib-sub">Apne default browser mein open karen</div>
  </div>
  <div class="ib-btns">
    <button class="ib-btn ib-open" id="ib-open-btn"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open in Browser</button>
    <button class="ib-btn ib-copy" id="ib-copy-btn"><i class="fa-solid fa-copy"></i> Copy Link</button>
  </div>
  <button id="ib-close-btn" onclick="document.getElementById('inapp-banner').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
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
      btn.style.background = '#d9f5e5';
      btn.style.borderColor = '#2a7a4b';
      btn.style.boxShadow = '3px 3px 0 #2a7a4b';
      setTimeout(function(){
        btn.innerHTML = '<i class="fa-solid fa-copy"></i> Copy Link';
        btn.style.background = '';
        btn.style.borderColor = '';
        btn.style.boxShadow = '';
      }, 2500);
    }).catch(function(){ window.prompt('Copy karo:', url); });
  });
})();
</script>
<!-- ── Custom cursor + Sound effects ── -->
<style>
#cc-dot,#cc-ring{position:fixed;border-radius:50%;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width .15s,height .15s,background .15s,opacity .15s;}
#cc-dot{width:10px;height:10px;background:var(--primary-color,#c084fc);border:2.5px solid #2d2a35;}
#cc-ring{width:30px;height:30px;border:2.5px solid #2d2a35;z-index:99998;background:transparent;}
.cc-hover #cc-dot{width:14px;height:14px;background:#2d2a35;}
.cc-hover #cc-ring{width:44px;height:44px;border-color:var(--primary-color,#c084fc);opacity:.55;}
.cc-active #cc-dot{transform:translate(-50%,-50%) scale(.7);}
.cc-active #cc-ring{transform:translate(-50%,-50%) scale(.85);}
html:has(#cc-dot){cursor:none!important;}
html:has(#cc-dot) a,html:has(#cc-dot) button,html:has(#cc-dot) input,html:has(#cc-dot) textarea,html:has(#cc-dot) select,html:has(#cc-dot) [onclick],html:has(#cc-dot) [tabindex]{cursor:none!important;}
#sound-toggle-btn{position:fixed;bottom:76px;right:20px;z-index:9998;width:40px;height:40px;border-radius:50%;background:var(--card-bg,#fff);border:2px solid #2d2a35;box-shadow:3px 3px 0 #2d2a35;font-size:1rem;line-height:1;cursor:none;display:flex;align-items:center;justify-content:center;transition:transform .18s,box-shadow .18s;padding:0;}
#sound-toggle-btn:hover{transform:translateY(-2px) scale(1.08);box-shadow:3px 5px 0 #2d2a35;}
</style>
<script>
(function(){
  /* ── Cursor (desktop only) ── */
  if('ontouchstart' in window) return;
  var dot=document.createElement('div'); dot.id='cc-dot';
  var ring=document.createElement('div'); ring.id='cc-ring';
  document.addEventListener('DOMContentLoaded',function(){
    document.body.appendChild(dot); document.body.appendChild(ring);
  });
  var mx=0,my=0,rx=0,ry=0;
  document.addEventListener('mousemove',function(e){
    mx=e.clientX; my=e.clientY;
    dot.style.left=mx+'px'; dot.style.top=my+'px';
  });
  document.addEventListener('mousedown',function(){ document.body.classList.add('cc-active'); });
  document.addEventListener('mouseup',function(){ document.body.classList.remove('cc-active'); });
  document.addEventListener('mouseover',function(e){
    var el=e.target.closest('a,button,[role=button],[onclick],input,textarea,select,[tabindex]');
    document.body.classList.toggle('cc-hover',!!el);
  });
  (function anim(){
    rx+=(mx-rx)*.16; ry+=(my-ry)*.16;
    ring.style.left=rx+'px'; ring.style.top=ry+'px';
    requestAnimationFrame(anim);
  })();

  /* ── Sound toggle button ── */
  document.addEventListener('DOMContentLoaded',function(){
    var btn=document.createElement('button');
    btn.id='sound-toggle-btn'; btn.title='Toggle sound effects';
    var on=localStorage.getItem('arigatoSound')!=='off';
    btn.innerHTML=on?'<i class="fa-solid fa-volume-high"></i>':'<i class="fa-solid fa-volume-xmark"></i>';
    btn.addEventListener('click',function(){
      on=!on; localStorage.setItem('arigatoSound',on?'on':'off');
      btn.innerHTML=on?'<i class="fa-solid fa-volume-high"></i>':'<i class="fa-solid fa-volume-xmark"></i>';
    });
    document.body.appendChild(btn);
  });
})();

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
