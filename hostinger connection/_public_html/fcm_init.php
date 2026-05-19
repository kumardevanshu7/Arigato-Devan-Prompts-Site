<?php
$_fcm_host = $_SERVER['HTTP_HOST'] ?? '';
$_fcm_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (!$_fcm_https || strpos($_fcm_host, 'localhost') !== false || strpos($_fcm_host, '127.0.0.1') !== false) {
    return;
}
?>
<!-- Firebase SDK preloaded (defer = non-blocking, ready before user clicks Allow) -->
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js" defer></script>
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js" defer></script>
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

    function showBannerError(msg){
        if(!banner) return;
        var errEl=banner.querySelector('.fcm-err');
        if(!errEl){ errEl=document.createElement('div'); errEl.className='fcm-err'; errEl.style.cssText='width:100%;font-size:0.72rem;color:#e03131;font-weight:700;margin-top:4px;text-align:center;'; banner.appendChild(errEl); }
        errEl.textContent=msg;
    }

    async function subscribeToFCM(){
        try{
            allowBtn.disabled=true;
            allowBtn.textContent='⏳';

            if(!window.firebase || !window.firebase.messaging){
                showBannerError('Firebase not ready. Refresh the page and try again.');
                allowBtn.disabled=false;
                allowBtn.textContent='Try Again 🔔';
                return;
            }

            if(!firebase.apps.length) firebase.initializeApp(FB_CONFIG);
            var messaging=firebase.messaging();

            // Register SW and wait for it to become active
            await navigator.serviceWorker.register('/firebase-messaging-sw.js');
            var swReg=await navigator.serviceWorker.ready;

            var permission=await Notification.requestPermission();
            if(permission!=='granted'){
                allowBtn.disabled=false;
                allowBtn.textContent='Allow 🔔';
                showBannerError('Permission denied — please allow in browser settings.');
                return;
            }

            var token=await messaging.getToken({vapidKey:VAPID,serviceWorkerRegistration:swReg});
            if(!token){
                showBannerError('Could not get token. Try again.');
                allowBtn.disabled=false;
                allowBtn.textContent='Allow 🔔';
                return;
            }

            var res=await fetch('/fcm_subscribe.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify({token:token})
            });
            var data=await res.json();
            if(!data.success) throw new Error(data.error||'Save failed');

            allowBtn.textContent='✅ Done!';
            allowBtn.style.background='#51cf66';
            setTimeout(function(){ dismissBanner(365); },1500);

        }catch(e){
            console.warn('FCM error:',e);
            showBannerError('Error: '+e.message);
            allowBtn.disabled=false;
            allowBtn.textContent='Try Again 🔔';
        }
    }

    window.addEventListener('load', function(){
        showBannerIfNeeded();
        if(allowBtn)  allowBtn.addEventListener('click',subscribeToFCM);
        if(dismissBtn) dismissBtn.addEventListener('click',function(){ dismissBanner(7); });
    });
})();
</script>
