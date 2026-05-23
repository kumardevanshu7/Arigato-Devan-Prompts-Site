<?php
$_gtag_script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$_gtag_canonical = 'https://arigatodevan.com' . strtok($_gtag_script, '?');
?>
<!-- Canonical URL -->
<link rel="canonical" href="<?= htmlspecialchars($_gtag_canonical) ?>">
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
</script>
<?php /* FCM disabled temporarily — re-enable by uncommenting: */ ?>
<?php // if (file_exists(__DIR__ . '/fcm_init.php')) include_once __DIR__ . '/fcm_init.php'; ?>
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
    btn.textContent=on?'🔊':'🔇';
    btn.addEventListener('click',function(){
      on=!on; localStorage.setItem('arigatoSound',on?'on':'off');
      btn.textContent=on?'🔊':'🔇';
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
