<?php
$_fcm_host = $_SERVER['HTTP_HOST'] ?? '';
$_fcm_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (!$_fcm_https || strpos($_fcm_host, 'localhost') !== false || strpos($_fcm_host, '127.0.0.1') !== false) {
    return;
}
?>
<!-- FCM Notification Banner -->
<div id="fcm-banner" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#fff;border:3px solid #2d2a35;border-radius:20px;padding:14px 18px;box-shadow:5px 5px 0 #2d2a35;z-index:99999;align-items:center;gap:12px;max-width:380px;width:calc(100% - 32px);font-family:'Outfit',sans-serif;animation:fcmSlideUp 0.4s ease;">
    <span style="font-size:1.6rem;flex-shrink:0;">🔔</span>
    <div style="flex:1;min-width:0;">
        <div style="font-weight:900;font-size:0.88rem;color:#2d2a35;line-height:1.3;">Get notified for new prompts!</div>
        <div style="font-size:0.75rem;color:#777;font-weight:600;margin-top:2px;">Be first to see every new AI couple prompt ✨</div>
    </div>
    <button id="fcm-allow-btn" style="background:#ff6b6b;color:#fff;border:2.5px solid #2d2a35;border-radius:12px;padding:8px 14px;font-weight:900;font-size:0.8rem;cursor:pointer;white-space:nowrap;font-family:inherit;flex-shrink:0;transition:transform 0.1s;">Allow 🔔</button>
    <button id="fcm-dismiss-btn" style="background:none;border:none;cursor:pointer;font-size:1rem;color:#aaa;padding:4px;flex-shrink:0;line-height:1;">✕</button>
</div>
<style>
@keyframes fcmSlideUp{from{opacity:0;transform:translateX(-50%) translateY(20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
#fcm-allow-btn:active{transform:translateY(2px)}
</style>
<script>
(function(){
    var VAPID = 'BIL2F3tv9S30n9Jic7bBDvxMjk1c3ZO2PpdhUxBga5N2RlXJI8oPyvCDMgMXMH4c0Y1MMPu88DwTTatKLb5OdVU';
    var FB_CONFIG = {
        apiKey:"AIzaSyBAzDxElpLX--lJ8xnvCrQBP-zYFMW_QLQ",
        authDomain:"arigato-devan-prompts.firebaseapp.com",
        projectId:"arigato-devan-prompts",
        storageBucket:"arigato-devan-prompts.firebasestorage.app",
        messagingSenderId:"770814780270",
        appId:"1:770814780270:web:03e1cd5de780452217d77f"
    };

    var banner    = document.getElementById('fcm-banner');
    var allowBtn  = document.getElementById('fcm-allow-btn');
    var dismissBtn= document.getElementById('fcm-dismiss-btn');

    function dismissBanner(days){
        if(banner) banner.style.display='none';
        localStorage.setItem('fcm_snoozed', Date.now()+(days*864e5));
    }

    function showBannerIfNeeded(){
        if(!('serviceWorker' in navigator)||!('PushManager' in window)) return;
        if(Notification.permission==='granted'||Notification.permission==='denied') return;
        var snoozed=localStorage.getItem('fcm_snoozed');
        if(snoozed && Date.now()<parseInt(snoozed)) return;
        setTimeout(function(){ if(banner) banner.style.display='flex'; }, 4000);
    }

    function loadScript(src){
        return new Promise(function(res,rej){
            var s=document.createElement('script');
            s.src=src; s.onload=res; s.onerror=rej;
            document.head.appendChild(s);
        });
    }

    async function subscribeToFCM(){
        try{
            allowBtn.disabled=true;
            allowBtn.textContent='⏳';

            if(!window.firebase){
                await loadScript('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
                await loadScript('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');
            }

            if(!firebase.apps.length) firebase.initializeApp(FB_CONFIG);
            var messaging=firebase.messaging();
            var sw=await navigator.serviceWorker.register('/firebase-messaging-sw.js');

            var permission=await Notification.requestPermission();
            if(permission!=='granted'){
                allowBtn.disabled=false;
                allowBtn.textContent='Allow 🔔';
                return;
            }

            var token=await messaging.getToken({vapidKey:VAPID,serviceWorkerRegistration:sw});
            if(!token) return;

            await fetch('/fcm_subscribe.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify({token:token})
            });

            allowBtn.textContent='✅ Done!';
            allowBtn.style.background='#51cf66';
            setTimeout(function(){ dismissBanner(365); },1500);

        }catch(e){
            console.warn('FCM error:',e);
            allowBtn.disabled=false;
            allowBtn.textContent='Allow 🔔';
        }
    }

    window.addEventListener('load', function(){
        showBannerIfNeeded();
        if(allowBtn)  allowBtn.addEventListener('click',subscribeToFCM);
        if(dismissBtn) dismissBtn.addEventListener('click',function(){ dismissBanner(7); });
    });
})();
</script>
