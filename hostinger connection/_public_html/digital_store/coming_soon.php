<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Coming Soon — Arigato Store</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #FAFAFA;
      --text: #111111;
      --accent: #c8a97e;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background-color: var(--bg);
      color: var(--text);
      font-family: 'Inter', sans-serif;
      height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 20px;
    }
    .logo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 40px;
      text-decoration: none;
      color: var(--text);
    }
    .logo img { height: 48px; }
    .logo-text { font-family: 'DM Serif Display', serif; font-size: 1.8rem; }
    .logo-dot { color: var(--accent); }
    
    h1 { font-family: 'DM Serif Display', serif; font-size: 4rem; line-height: 1.1; margin-bottom: 20px; letter-spacing: -0.02em; }
    p { font-size: 1.1rem; color: #555; max-width: 500px; line-height: 1.6; margin-bottom: 40px; }
    .badge { display: inline-block; padding: 6px 16px; border: 1px solid rgba(0,0,0,0.1); border-radius: 50px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 24px; color: #666; }
    .back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: var(--text); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 500; transition: opacity 0.2s; }
    .back-btn:hover { opacity: 0.8; }
    @media (max-width: 768px) { h1 { font-size: 2.8rem; } }
  </style>
</head>
<body>
  <a href="../index.php" class="logo">
    <img src="../toplogo/logo01.webp" alt="Arigato Logo"/>
    <span class="logo-text">Arigato<span class="logo-dot">.</span>Store</span>
  </a>
  <div class="badge">Exclusive Access</div>
  <h1>Something Premium is Coming.</h1>
  <p>We are currently crafting an exclusive collection of high-end AI prompts. The digital store is currently in private beta testing.</p>
  <a href="../index.php" class="back-btn">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
    Return to Arigato
  </a>
</body>
</html>
