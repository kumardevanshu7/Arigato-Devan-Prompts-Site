<?php
require_once __DIR__ . '/../includes/session_bootstrap.php';

/**
 * Store FAQ. Questions live in this array so the visible accordion and the
 * FAQPage JSON-LD are always generated from the same source.
 * Answers may contain inline HTML — schema output is stripped to plain text.
 */
$faq_groups = [
    [
        'title' => 'Buying &amp; Payments',
        'items' => [
            [
                'q' => 'What exactly do I get when I buy a prompt?',
                'a' => 'You get the <strong>complete prompt text</strong> — copy-paste ready, with no blurred or missing lines. Most products also include a short "How to Use" guide explaining which photo to upload, what to change (names, outfit, background), and the settings that gave us the sample results. Where a PDF guide or Drive file is part of the pack, that is unlocked too. You are buying the prompt (the instructions), not the sample images themselves.',
            ],
            [
                'q' => 'How do I pay, and is my payment safe?',
                'a' => 'Checkout is handled by <strong>SuperProfile</strong>, our payment partner. When you click "Unlock", you are taken to a secure SuperProfile page where you can pay by <strong>UPI, debit or credit card, net banking, or wallet</strong>. All prices are in Indian Rupees (₹). Arigato Store never sees or stores your card number, UPI ID, or banking credentials — those stay with SuperProfile and its payment gateways.',
            ],
            [
                'q' => 'Do I need an account to buy a prompt?',
                'a' => 'No. If you are not logged in, a small popup asks for your <strong>email address</strong> so your purchase can be linked to you and your receipt can be delivered. Signing in with Google is optional but recommended — it unlocks the <strong>My Purchases</strong> page so you can reopen every prompt you have bought, any time.',
            ],
            [
                'q' => 'How quickly is the prompt delivered?',
                'a' => 'Instantly. The moment your payment succeeds you are redirected back to Arigato Store and taken to a secure page showing the full unlocked prompt. SuperProfile also emails you a receipt. There is no waiting, no manual approval, and nothing is shipped.',
            ],
            [
                'q' => 'I paid but did not receive my prompt. What should I do?',
                'a' => 'Do not re-pay. <a href="contact.php">Raise a support ticket</a> with your payment screenshot or the SuperProfile receipt email, and we will restore your access — normally <strong>within 48 hours</strong>. You can also email <strong>devansh.grow@gmail.com</strong> directly.',
            ],
            [
                'q' => 'Do you offer refunds?',
                'a' => 'Because a prompt is digital and is revealed in full the instant it is delivered, <strong>all sales are final once the prompt has been shown to you</strong>. The one clear exception: if you paid and never received access, we fix it or refund it. Full details are in our <a href="terms.php">Terms &amp; Conditions</a>, and nothing here removes rights you have under Indian consumer law.',
            ],
            [
                'q' => 'Where can I find a prompt I bought earlier?',
                'a' => 'If you were signed in at the time of purchase, open <strong>My Purchases</strong> from the top-right of the store — every prompt you own stays there permanently. The success link shown right after payment is <strong>single-use</strong> for security, so it will not reopen. Keep the SuperProfile receipt email as your backup proof of purchase.',
            ],
            [
                'q' => 'Are the prices fixed? Will they change later?',
                'a' => 'Prices are listed in <strong>₹ INR</strong> and can change at any time — launch prices and discounts are often temporary. Whatever price you see at checkout is the price you pay, and it is locked in for that purchase.',
            ],
        ],
    ],
    [
        'title' => 'Using Your Couple Prompts',
        'items' => [
            [
                'q' => 'Which AI tools do these couple prompts work with?',
                'a' => 'Every prompt is written and tested for <strong>Google Gemini (Nano Banana)</strong> and <strong>ChatGPT Image</strong> — the two tools that handle real photo-to-image couple edits best. Many prompts will also work in other image models, but we only promise the results you see for Gemini and ChatGPT, because those are what we actually test on.',
            ],
            [
                'q' => 'Will the AI keep our real faces in the picture?',
                'a' => 'That is exactly what these prompts are built for. Each one is engineered to hold facial features as close to your uploaded photo as possible. Accuracy still depends on your input: use a <strong>clear, front-facing, well-lit photo</strong> where both faces are visible and unobstructed. Blurry group shots, heavy filters, sunglasses, or side profiles are the usual reason a face comes out different. If the first result is off, re-run the prompt — a second attempt with a better photo usually fixes it.',
            ],
            [
                'q' => 'Will I get the exact same image as the sample photos?',
                'a' => 'No — and no honest prompt seller can promise that. AI image tools are <strong>non-deterministic</strong>: the same prompt produces a slightly different image each time, and your photo, your faces, and your outfits are different from ours. The samples show you the <strong>style, lighting, framing, and mood</strong> you can expect, not a pixel-identical guarantee. Read our <a href="disclaimer.php">Disclaimer</a> for the full picture.',
            ],
            [
                'q' => 'Do I need a paid Gemini or ChatGPT subscription?',
                'a' => 'Not necessarily. Most prompts run on the <strong>free tiers</strong> of Gemini and ChatGPT, though free plans have daily image limits and are sometimes slower or lower resolution. A paid plan gives you more generations and better quality, but that is a separate subscription with Google or OpenAI — it is not sold by us and not included in your purchase.',
            ],
            [
                'q' => 'What kinds of couple prompts are in the store?',
                'a' => 'The collection is built around <strong>Indian couple photography</strong>: pre-wedding and wedding shoots, saree and lehenga portraits, retro Bollywood and vintage film looks, rainy and monsoon frames, café and travel candids, and festival sets for <strong>Navratri, Diwali, Karwa Chauth and Valentine\'s Day</strong>, plus anniversary, birthday, and cute cartoon or 3D-style couple edits. New drops are added regularly.',
            ],
            [
                'q' => 'Can I post the results on Instagram or use them commercially?',
                'a' => 'Yes. Your purchase gives you a <strong>personal, non-exclusive licence</strong> to use the prompt and the images you create from it — for your own reels and posts, for client work, and for commercial projects. You must also follow the terms of the AI tool you generated with, since Google and OpenAI set their own rules on output usage.',
            ],
            [
                'q' => 'Can I share the prompt text with my partner or friends?',
                'a' => 'One purchase covers <strong>one buyer</strong>. You are welcome to use the prompt on your own photos as many times as you like, but you may not <strong>resell, republish, or post the prompt text publicly</strong>, or pass your single-use access link around. That is the only thing keeping premium prompts worth buying — for us and for everyone who paid.',
            ],
            [
                'q' => 'What if the prompt stops working after an AI update?',
                'a' => 'AI models change often, and wording that worked last month can behave differently after an update. When we notice a prompt drifting, we <strong>revise it and the updated version appears in My Purchases at no extra cost</strong>. If something is not working for you, <a href="contact.php">tell us</a> — that feedback is genuinely how these prompts get better.',
            ],
            [
                'q' => 'How are these different from the free prompts on the main site?',
                'a' => 'The main <a href="../index.php">Arigato Devan</a> site has hundreds of <strong>free couple prompts</strong> you unlock by tapping or with an Instagram secret code. Store prompts are the premium set: longer, more precisely engineered, tested across more photos, and never published for free. If you are new, start with the free prompts — buy from the store when you want a specific look nailed on the first try.',
            ],
        ],
    ],
];

// Build FAQPage structured data from the same array.
$schema_items = [];
foreach ($faq_groups as $group) {
    foreach ($group['items'] as $item) {
        $schema_items[] = [
            '@type' => 'Question',
            'name'  => html_entity_decode(strip_tags($item['q']), ENT_QUOTES, 'UTF-8'),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => html_entity_decode(strip_tags($item['a']), ENT_QUOTES, 'UTF-8'),
            ],
        ];
    }
}
$faq_schema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => $schema_items,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FAQ — Buying AI Couple Prompts | Arigato Store</title>
  <meta name="description" content="Answers about buying AI couple prompts from Arigato Store — SuperProfile payments, instant delivery, refunds, licence, and how to use prompts in Gemini and ChatGPT."/>
  <meta name="keywords" content="ai couple prompts, couple prompts for gemini, buy ai prompts india, couple prompts for chatgpt, indian wedding couple prompts, premium couple prompts, prompt store faq"/>
  <meta name="robots" content="index, follow"/>
  <link rel="canonical" href="https://arigatodevan.com/digital_store/faq.php"/>
  <meta property="og:type" content="website"/>
  <meta property="og:title" content="FAQ — Buying AI Couple Prompts | Arigato Store"/>
  <meta property="og:description" content="Payments, instant delivery, refunds, licence and usage — everything about buying premium AI couple prompts from Arigato Store."/>
  <meta property="og:url" content="https://arigatodevan.com/digital_store/faq.php"/>
  <meta name="twitter:card" content="summary_large_image"/>
  <link rel="icon" href="/favicon.ico" type="image/x-icon"/>
  <link rel="stylesheet" href="css/store.css"/>
  <script type="application/ld+json"><?= json_encode($faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
  <style>
    .faq-page { max-width: 820px; margin: 0 auto; padding: 60px 20px 80px; }

    .faq-head { text-align: center; margin-bottom: 40px; }
    .faq-label {
      display: inline-block; background: #f8f4ef; color: #8b6914;
      border: 1px solid #e5d5b0; border-radius: 100px;
      font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em;
      text-transform: uppercase; padding: 5px 14px; margin-bottom: 16px;
    }
    .faq-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2rem, 5vw, 2.9rem); font-weight: 900;
      color: var(--text-primary); margin-bottom: 12px; line-height: 1.15;
    }
    .faq-sub {
      font-size: 0.95rem; color: var(--text-muted);
      line-height: 1.75; max-width: 600px; margin: 0 auto;
    }

    /* Quick links */
    .faq-quick {
      display: grid; grid-template-columns: repeat(3, 1fr);
      gap: 12px; margin: 32px 0 48px;
    }
    .faq-quick a {
      display: flex; align-items: center; gap: 10px;
      background: var(--bg-card); border: 1.5px solid var(--border);
      border-radius: 14px; padding: 14px 16px;
      font-size: 0.82rem; font-weight: 600;
      color: var(--text-secondary); text-decoration: none;
      transition: all 0.2s;
    }
    .faq-quick a:hover {
      border-color: #c9a96e; color: var(--text-primary);
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(0,0,0,0.05);
    }
    .faq-quick svg { color: #c9a96e; flex-shrink: 0; }

    /* Groups */
    .faq-group { margin-bottom: 44px; }
    .faq-group-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.3rem; font-weight: 800; color: var(--text-primary);
      margin-bottom: 18px; padding-bottom: 12px;
      border-bottom: 1px solid var(--border);
    }

    /* Accordion */
    .faq-item {
      background: var(--bg-card); border: 1.5px solid var(--border);
      border-radius: 14px; margin-bottom: 10px; overflow: hidden;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .faq-item.open {
      border-color: #c9a96e;
      box-shadow: 0 6px 20px rgba(201,169,110,0.12);
    }
    .faq-q {
      width: 100%; display: flex; align-items: center;
      justify-content: space-between; gap: 14px;
      background: none; border: none; cursor: pointer;
      padding: 17px 20px; text-align: left;
      font-family: inherit; font-size: 0.92rem; font-weight: 700;
      color: var(--text-primary); line-height: 1.5;
    }
    .faq-q:hover { color: #8b6914; }
    .faq-ico {
      flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%;
      background: #f8f4ef; color: #8b6914;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 0.9rem; font-weight: 700; line-height: 1;
      transition: transform 0.25s;
    }
    .faq-item.open .faq-ico { transform: rotate(45deg); }
    .faq-a {
      max-height: 0; overflow: hidden;
      transition: max-height 0.3s ease;
    }
    .faq-a-inner {
      padding: 0 20px 18px;
      font-size: 0.87rem; color: var(--text-secondary); line-height: 1.8;
    }
    .faq-a-inner a { color: var(--text-primary); font-weight: 600; }

    /* Closing CTA */
    .faq-cta {
      text-align: center; background: #f8f4ef;
      border: 1px solid #e5d5b0; border-radius: 18px;
      padding: 34px 24px; margin-top: 8px;
    }
    .faq-cta h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.35rem; font-weight: 800;
      color: var(--text-primary); margin-bottom: 10px;
    }
    .faq-cta p {
      font-size: 0.88rem; color: var(--text-secondary);
      line-height: 1.75; margin-bottom: 20px;
    }
    .faq-cta-btn {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--text-primary); color: #fff;
      border-radius: 100px; padding: 12px 26px;
      font-size: 0.85rem; font-weight: 700; text-decoration: none;
      transition: transform 0.2s, opacity 0.2s;
    }
    .faq-cta-btn:hover { transform: translateY(-2px); opacity: 0.9; }

    @media (max-width: 640px) {
      .faq-page { padding: 40px 16px 60px; }
      .faq-quick { grid-template-columns: 1fr; }
      .faq-q { font-size: 0.88rem; padding: 15px 16px; }
      .faq-a-inner { padding: 0 16px 16px; }
    }
  </style>
</head>
<body>
<?php include 'store_nav.php'; ?>

<main class="faq-page">

  <div class="faq-head">
    <span class="faq-label">Help Center</span>
    <h1 class="faq-title">Frequently Asked Questions</h1>
    <p class="faq-sub">Everything about buying premium AI couple prompts from Arigato Store — how payment works, how fast you get your prompt, what you are allowed to do with it, and how to get the best results out of Gemini and ChatGPT.</p>
  </div>

  <div class="faq-quick">
    <a href="my_purchases.php">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      My Purchases
    </a>
    <a href="contact.php">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Raise a Ticket
    </a>
    <a href="terms.php">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Terms &amp; Refunds
    </a>
  </div>

  <?php foreach ($faq_groups as $group): ?>
    <section class="faq-group">
      <h2 class="faq-group-title"><?= $group['title'] ?></h2>
      <?php foreach ($group['items'] as $item): ?>
        <div class="faq-item">
          <button type="button" class="faq-q" aria-expanded="false">
            <span><?= $item['q'] ?></span>
            <span class="faq-ico" aria-hidden="true">+</span>
          </button>
          <div class="faq-a">
            <div class="faq-a-inner"><?= $item['a'] ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>

  <div class="faq-cta">
    <h2>Still have a question?</h2>
    <p>If your answer is not here, ask us directly. Support tickets about payments or missing access are answered within 48 hours.</p>
    <a href="contact.php" class="faq-cta-btn">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      Contact Support
    </a>
  </div>

</main>

<?php include 'store_footer_links.php'; ?>
<?php include '../footer.php'; ?>

<script>
document.querySelectorAll('.faq-q').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var item   = btn.closest('.faq-item');
    var answer = item.querySelector('.faq-a');
    var isOpen = item.classList.contains('open');

    document.querySelectorAll('.faq-item.open').forEach(function (openItem) {
      openItem.classList.remove('open');
      openItem.querySelector('.faq-a').style.maxHeight = null;
      openItem.querySelector('.faq-q').setAttribute('aria-expanded', 'false');
    });

    if (!isOpen) {
      item.classList.add('open');
      answer.style.maxHeight = answer.scrollHeight + 'px';
      btn.setAttribute('aria-expanded', 'true');
    }
  });
});
</script>
<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
