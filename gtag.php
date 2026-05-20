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
