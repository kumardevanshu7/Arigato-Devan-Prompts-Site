<!-- =============================================
  store_firebase_js.php — Firebase Google Sign-In
  Included at bottom of every digital store page.
  Shows "Sign in with Google" button in navbar when logged out.
  After successful login: reloads page → PHP session shows Admin button.
  ============================================= -->
<?php if (!isset($_SESSION['user_id'])): ?>
<script type="module">
  import { initializeApp }                          from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
  import { getAuth, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";

  const firebaseConfig = {
    apiKey:            "AIzaSyBAzDxElpLX--lJ8xnvCrQBP-zYFMW_QLQ",
    authDomain:        "arigato-devan-prompts.firebaseapp.com",
    projectId:         "arigato-devan-prompts",
    storageBucket:     "arigato-devan-prompts.firebasestorage.app",
    messagingSenderId: "770814780270",
    appId:             "1:770814780270:web:03e1cd5de780452217d77f"
  };

  const app      = initializeApp(firebaseConfig);
  const auth     = getAuth(app);
  const provider = new GoogleAuthProvider();
  provider.setCustomParameters({ prompt: 'select_account' });

  const loginBtn = document.getElementById('storeGoogleLoginBtn');
  if (loginBtn) {
    loginBtn.addEventListener('click', async () => {
      loginBtn.disabled = true;
      loginBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 11-4-7.5"/></svg> Signing in...';

      try {
        const result  = await signInWithPopup(auth, provider);
        const idToken = await result.user.getIdToken();

        const res  = await fetch('store_auth.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ idToken })
        });
        const data = await res.json();

        if (data.success) {
          // Reload so PHP session kicks in and navbar updates (admin button appears if admin)
          window.location.reload();
        } else {
          alert('Login failed: ' + (data.error || 'Unknown error'));
          loginBtn.disabled = false;
          loginBtn.innerHTML = '<img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="16" height="16" alt="Google"/> Sign in';
        }
      } catch (err) {
        if (err.code !== 'auth/popup-closed-by-user') {
          alert('Authentication error. Please try again.');
        }
        loginBtn.disabled = false;
        loginBtn.innerHTML = '<img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="16" height="16" alt="Google"/> Sign in';
      }
    });
  }
</script>
<style>
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
<?php endif; ?>
