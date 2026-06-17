<?php
$_page_canonical = 'https://arigatodevan.com/how_to_use.php';
session_start();
require_once "db.php";
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php"); exit();
}
$is_li   = isset($_SESSION["user_id"]);
$active  = in_array($_GET['cat'] ?? '', ['secret_code','insta_viral','unreleased','already_uploaded'])
           ? $_GET['cat'] : 'secret_code';

// -- Category data -------------------------------------------------------------
// Steps: [icon, title_en, title_hi, desc_en, desc_hi, tip_en, tip_hi]
$cats = [
  'secret_code' => [
    'label'   => 'Secret Code',  'icon' => 'fa-solid fa-lock',
    'accent'  => '#FFD166',      'link' => 'secret_code.php',
    'tag_en'  => 'Watch reel � Enter code � Unlock',
    'tag_hi'  => 'Reel dekho � Code daalo � Unlock karo',
    'steps'   => [
      ['fa-brands fa-instagram','Watch the Reel','Reel Dekho',
       'Open Instagram and follow @arigato.devan. Watch the latest reel carefully � the 6-character secret code appears in the caption, pinned comment, or flashes briefly in the video.',
       'Instagram kholke @arigato.devan follow karo. Latest reel dhyan se dekho � 6-character secret code caption mein, pinned comment mein, ya video mein briefly flash hota hai.',
       'Pause the video to spot the code clearly!','Code clearly dekhne ke liye video pause karo!'],
      ['fa-solid fa-keyboard','Enter the Code','Code Enter Karo',
       'On the Secret Code page, tap any locked prompt card. A modal opens with a text input � type the exact 6-letter code. It auto-converts to uppercase, so no worries about case.',
       'Secret Code page pe koi bhi locked prompt card tapao. Ek modal khulega text input ke saath � exactly wahi 6-letter code type karo. Auto uppercase ho jaata hai.',
       null, null],
      ['fa-solid fa-wand-magic-sparkles','Unlock the Prompt','Prompt Unlock Karo',
       'Hit "Generate Prompt". Correct code = your prompt reveals instantly! Wrong code? Re-watch the reel carefully � the code is exactly 6 uppercase characters, no spaces.',
       '"Generate Prompt" dabaao. Sahi code = prompt turant reveal! Wrong code? Reel dubara dhyan se dekho � exactly 6 uppercase characters hain, koi space nahi.',
       null, null],
      ['fa-solid fa-copy','Copy the Prompt','Prompt Copy Karo',
       'Tap COPY below the revealed prompt. The full text copies to your clipboard instantly. Tap SAVE to store it permanently in your profile.',
       'Revealed prompt ke niche COPY dabaao. Poora text clipboard mein copy ho jaata hai. SAVE dabao toh profile mein permanently store ho jaayega.',
       'Login users can save prompts � never lose them again!','Login karo toh prompts save ho jaate hain � kabhi nahi khote!'],
      ['fa-solid fa-robot','Paste on Gemini & Create!','Gemini Pe Daalo aur Banao!',
       'Open gemini.google.com, click the message box, paste the prompt (Ctrl+V / long-press ? Paste), hit Enter. Watch AI generate stunning couple content in seconds!',
       'gemini.google.com kholke message box mein prompt paste karo (Ctrl+V ya long-press ? Paste) aur Enter dabaao. Seconds mein AI stunning couple content banaata hai!',
       'Use Gemini 2.0 Flash Experimental for the best image results!','Best image results ke liye Gemini 2.0 Flash Experimental use karo!'],
    ],
  ],
  'insta_viral' => [
    'label'   => 'Insta Viral',  'icon' => 'fa-brands fa-instagram',
    'accent'  => '#dc2743',      'link' => 'insta_viral.php',
    'tag_en'  => 'Solve math � Unlock � Go viral',
    'tag_hi'  => 'Math solve karo � Unlock karo � Viral ho jao',
    'steps'   => [
      ['fa-solid fa-calculator','Solve the Math Challenge','Math Challenge Solve Karo',
       'Tap any prompt card on the Insta Viral page. A math challenge pops up � like "12 � 4 = ?". Solve it and enter your answer. You get unlimited tries, so no pressure!',
       'Insta Viral page pe koi bhi prompt card tapao. Ek math challenge aayegi � jaise "12 � 4 = ?". Solve karke answer type karo. Unlimited tries milte hain!',
       'The math is always basic arithmetic � no tricks!','Math hamesha basic arithmetic hoti hai � koi trick nahi!'],
      ['fa-solid fa-lock-open','Prompt Unlocked!','Prompt Unlock Hua!',
       'Correct answer = instant reveal! Each prompt card has its own unique math challenge. No code needed, no tapping � just brainpower!',
       'Sahi answer = instant reveal! Har prompt card ka apna unique math challenge hota hai. Koi code nahi, koi tapping nahi � sirf dimaag lagao!',
       null, null],
      ['fa-solid fa-copy','Copy the Prompt','Prompt Copy Karo',
       'Tap the COPY button on the unlocked card to grab the full viral prompt text.',
       'Unlocked card pe COPY button dabaake poora viral prompt text copy karo.',
       null, null],
      ['fa-solid fa-fire','Paste on Gemini & Go Viral!','Gemini Pe Daalo aur Viral Ho!',
       'These are our most viral, tested prompts from top-performing reels. Paste on Gemini AI and generate Instagram couple content that actually goes viral!',
       'Ye hamare most viral, tested prompts hain top-performing reels se. Gemini AI pe paste karo aur Instagram couple content banao jo actually viral ho!',
       'For image generation use ChatGPT Image 4 or Gemini!','Image generation ke liye ChatGPT Image 4 ya Gemini use karo!'],
    ],
  ],
  'unreleased' => [
    'label'   => 'Unreleased',   'icon' => 'fa-solid fa-heart',
    'accent'  => '#C084FC',      'link' => 'unreleased.php',
    'tag_en'  => '20 taps (login) � 90 taps (guest) � Unlock',
    'tag_hi'  => '20 taps (login) � 90 taps (guest) � Unlock karo',
    'steps'   => [
      ['fa-solid fa-heart','Tap the Love Bar','Love Bar Tapao',
       'Find the pulsing Love Bar at the bottom of each prompt card. Tap it rapidly and repeatedly! Logged-in users need just 20 taps � guests need 90. Every single tap counts!',
       'Har prompt card ke niche pulsing Love Bar dhundho. Baar baar tapte raho! Login ke saath sirf 20 taps chahiye � guests ko 90. Har ek tap count hota hai!',
       'Login for 20 taps instead of 90 � 4.5x faster!','Login karo toh 90 ki jagah sirf 20 taps � 4.5 guna faster!'],
      ['fa-solid fa-lock-open','Auto-Unlocked!','Auto-Unlock Ho Gaya!',
       'Hit the threshold and the prompt unlocks automatically with a satisfying animation. Logged-in users have their tap progress saved � so you can return and continue anytime!',
       'Threshold hit karo aur prompt automatically unlock ho jaata hai. Login ke saath tap progress save hoti hai � kisi bhi time wapas aakar continue kar sakte ho!',
       null, null],
      ['fa-solid fa-copy','Copy the Prompt','Prompt Copy Karo',
       'One tap on COPY and the full exclusive prompt is in your clipboard.',
       'COPY pe ek tap aur poora exclusive prompt clipboard mein aa jaata hai.',
       null, null],
      ['fa-solid fa-star','Create Before Anyone Else!','Sabse Pehle Content Banao!',
       'These prompts have NEVER been released publicly before. Take them to Gemini and create content that nobody else has made yet � be first!',
       'Ye prompts pehle kabhi publicly release nahi hue. Gemini pe le jaao aur aisa content banao jo aur kisi ne nahi banaya � sabse pehle bano!',
       'Unreleased prompts are refreshed regularly � check back often!','Unreleased prompts regularly refresh hote hain � baar baar check karo!'],
    ],
  ],
  'already_uploaded' => [
    'label'   => 'Already Uploaded', 'icon' => 'fa-solid fa-clock-rotate-left',
    'accent'  => '#60A5FA',           'link' => 'already_uploaded.php',
    'tag_en'  => 'Just 9 taps � Unlock � Recreate viral content',
    'tag_hi'  => 'Sirf 9 taps � Unlock karo � Viral content recreate karo',
    'steps'   => [
      ['fa-solid fa-hand-pointer','Tap Just 9 Times','Sirf 9 Baar Tapao',
       'Tap the prompt card 9 times � that\'s all! A tap counter shows your progress. This is hands-down the easiest unlock method on the entire site.',
       'Prompt card ko 9 baar tapao � bas itna! Tap counter progress dikhata hai. Ye poori site ka sabse aasaan unlock method hai.',
       'Tap anywhere on the card � the full card area is tappable!','Card pe kahin bhi tapao � poora card area tappable hai!'],
      ['fa-solid fa-lock-open','Prompt Revealed!','Prompt Reveal Hua!',
       'On the 9th tap � instant reveal! Logged-in users have their unlocked status saved permanently, even across different devices.',
       '9we tap pe � instant reveal! Login ke saath unlock status permanently save hota hai, alag alag devices pe bhi.',
       null, null],
      ['fa-solid fa-copy','Copy the Prompt','Prompt Copy Karo',
       'Hit COPY to grab the proven viral prompt text. These are actual captions and prompts from our highest-performing Instagram reels.',
       'COPY dabao aur proven viral prompt text lo. Ye hamare highest-performing Instagram reels ke actual captions hain.',
       null, null],
      ['fa-solid fa-rotate','Recreate the Viral Magic!','Viral Magic Recreate Karo!',
       'These prompts powered reels that got thousands of views and saves. Paste on Gemini AI and recreate the exact magic � your audience will love it!',
       'Ye prompts ne hazaron views aur saves wale reels banaye. Gemini AI pe paste karo aur exact wahi magic recreate karo � audience love karegi!',
       'Try these on ChatGPT Image 4o for stunning AI couple photos!','Stunning AI couple photos ke liye ChatGPT Image 4o try karo!'],
    ],
  ],
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>How to Use � Arigato Devan Prompts</title>
<meta name="description" content="Complete step-by-step guide to unlocking and using AI prompts on Arigato Devan. Learn how to use Secret Code, Insta Viral, Unreleased, and Already Uploaded prompts."><link rel="canonical" href="https://arigatodevan.com/how_to_use.php">
<link rel="stylesheet" href="style.min.css?v=20260601">
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<?php include_once "gtag.php"; ?>
<style>
/* -- Layout --------------------------------------------------------------- */
.htu-page{max-width:1300px;margin:40px auto 100px;padding:0 20px;}

/* -- Hero ----------------------------------------------------------------- */
.htu-hero{text-align:center;padding:70px 30px 60px;position:relative;overflow:hidden;background:rgba(255,255,255,0.85);backdrop-filter:blur(20px);border:var(--border-width) solid var(--text-color);border-radius:40px 40px 0 0;box-shadow:8px 8px 0 var(--text-color);}
.htu-hero::before{content:'';position:absolute;inset:0;background:repeating-linear-gradient(45deg,transparent,transparent 18px,var(--primary-color) 18px,var(--primary-color) 20px);opacity:.04;pointer-events:none;}
.htu-hero-badge{display:inline-flex;align-items:center;gap:8px;background:var(--secondary-color);border:2px solid var(--text-color);border-radius:40px;padding:8px 22px;font-weight:900;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:24px;box-shadow:3px 3px 0 var(--text-color);transform:rotate(-2deg);}
.htu-hero h1{font-size:clamp(2.5rem,6vw,4rem);font-weight:900;letter-spacing:-2px;margin-bottom:16px;line-height:1.05;font-family:var(--font-main);}
.htu-hero p{color:#444;font-weight:700;font-size:1.15rem;max-width:540px;margin:0 auto 32px;}
.htu-lang-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:var(--text-color);color:#fff;border:none;border-radius:40px;font-family:var(--font-main);font-weight:900;font-size:.95rem;cursor:pointer;box-shadow:4px 4px 0 rgba(0,0,0,0.3);transition:all .2s;text-transform:uppercase;letter-spacing:1px;}
.htu-lang-btn:hover{background:#000;transform:translateY(-3px);box-shadow:4px 6px 0 rgba(0,0,0,0.3);}

/* -- Sticky Tabs ----------------------------------------------------------- */
.htu-tabs-bar{position:sticky;top:80px;z-index:200;background:rgba(255,255,255,0.9);backdrop-filter:blur(16px);border:var(--border-width) solid var(--text-color);border-top:none;padding:16px 20px;border-radius:0 0 40px 40px;box-shadow:8px 8px 0 var(--text-color);margin-bottom:40px;}
.htu-tabs{display:flex;gap:14px;overflow-x:auto;overflow-y:visible;max-width:1260px;margin:0 auto;scrollbar-width:none;padding:6px 2px 12px;}
.htu-tabs::-webkit-scrollbar{display:none;}
.htu-tab{flex-shrink:0;display:inline-flex;align-items:center;gap:8px;padding:12px 26px;background:var(--bg-color);border:2px solid var(--text-color);border-radius:40px;font-family:var(--font-main);font-weight:900;font-size:.9rem;cursor:pointer;transition:all .2s;color:var(--text-color);box-shadow:3px 3px 0 var(--text-color);}
.htu-tab:hover{background:var(--primary-color);transform:translateY(-2px);box-shadow:4px 5px 0 var(--text-color);}
.htu-tab.active{background:var(--text-color);color:#fff;box-shadow:4px 4px 0 rgba(0,0,0,0.3);transform:translateY(-2px);}

/* -- Panel ----------------------------------------------------------------- */
.htu-panel{display:none;padding:0 16px;animation:htu-fadein .4s ease;}
.htu-panel.active{display:block;}
@keyframes htu-fadein{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}

/* -- Category header ------------------------------------------------------- */
.htu-cat-head{display:flex;align-items:center;gap:24px;margin-bottom:40px;flex-wrap:wrap;background:rgba(255,255,255,0.9);backdrop-filter:blur(16px);border:var(--border-width) solid var(--text-color);border-radius:30px;padding:30px;box-shadow:6px 6px 0 var(--text-color);}
.htu-cat-accent{width:8px;flex-shrink:0;border-radius:10px;min-height:80px;align-self:stretch;}
.htu-cat-info{flex:1;}
.htu-cat-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 20px;border:2px solid var(--text-color);border-radius:40px;font-weight:900;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;}
.htu-cat-tagline{font-size:1.1rem;color:#444;font-weight:700;margin:0;}
.htu-cat-link{display:inline-flex;align-items:center;gap:8px;margin-top:20px;padding:12px 28px;background:var(--text-color);color:#fff;border:none;border-radius:40px;font-family:var(--font-main);font-weight:900;font-size:.95rem;text-decoration:none;box-shadow:4px 4px 0 rgba(0,0,0,0.2);transition:all .2s;}
.htu-cat-link:hover{transform:translateY(-3px);box-shadow:4px 6px 0 rgba(0,0,0,0.2);background:#000;}

/* -- Steps Grid (Desktop) -------------------------------------------------- */
.htu-steps-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:30px;margin-bottom:50px;}
.htu-step-card{position:relative;background:rgba(255,255,255,0.95);border:var(--border-width) solid var(--text-color);border-radius:30px;padding:36px 28px 28px;box-shadow:6px 6px 0 var(--text-color);cursor:default;transition:all .2s ease;will-change:transform;animation:htu-fadein .4s ease both;animation-delay:var(--htu-delay,0s);}
.htu-step-card:hover{transform:translateY(-6px);box-shadow:8px 8px 0 var(--text-color);}
.htu-step-num{position:absolute;top:-16px;left:-12px;width:40px;height:40px;background:var(--text-color);color:var(--bg-color);border-radius:50%;font-size:1.1rem;font-weight:900;display:flex;align-items:center;justify-content:center;font-family:var(--font-main);border:3px solid var(--bg-color);line-height:1;box-shadow:3px 3px 0 rgba(0,0,0,0.2);transform:rotate(-10deg);}
.htu-step-icon{width:56px;height:56px;border-radius:18px;border:3px solid var(--text-color);display:flex;align-items:center;justify-content:center;margin-bottom:20px;transition:transform .2s;}
.htu-step-icon i{font-size:1.4rem;color:var(--text-color);}
.htu-step-card:hover .htu-step-icon{transform:scale(1.15) rotate(-8deg);}
.htu-step-title{font-size:1.1rem;font-weight:900;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;color:var(--text-color);font-family:var(--font-main);line-height:1.2;}
.htu-step-desc{font-size:.95rem;color:#444;font-weight:600;line-height:1.6;}
.htu-step-tip{margin-top:18px;padding:12px 16px;background:var(--secondary-color);border:2px solid var(--text-color);border-radius:14px;font-size:.85rem;font-weight:800;display:flex;align-items:flex-start;gap:10px;line-height:1.4;box-shadow:2px 2px 0 var(--text-color);}
.htu-step-tip i{flex-shrink:0;margin-top:1px;color:#9E6A00;}
.htu-hidden{display:none;}

/* -- Mobile: vertical timeline --------------------------------------------- */
@media(max-width:700px){
  .htu-page{margin:20px auto 60px;padding:0 12px;}
  .htu-hero{padding:40px 20px 30px;border-radius:30px 30px 0 0;}
  .htu-tabs-bar{top:70px;padding:12px;border-radius:0 0 30px 30px;margin-bottom:30px;}
  .htu-hero h1{letter-spacing:-1px;font-size:2.2rem;}
  .htu-hero p{font-size:1rem;}
  .htu-cat-head{padding:20px;border-radius:24px;gap:16px;}
  .htu-panel{padding:0 8px;}
  .htu-steps-grid{grid-template-columns:1fr;gap:20px;}
  .htu-step-card{display:flex;flex-direction:column;border-radius:24px;padding:28px 20px 20px;}
  .htu-step-card .htu-step-num{top:-12px;left:-8px;}
  .htu-step-icon{margin-bottom:16px;}
  .htu-step-card:hover{transform:translateY(-2px);box-shadow:6px 6px 0 var(--text-color);}
}
</style>
<script type="application/ld+json">
[
<?php foreach ($cats as $key => $cat):
  $steps_json = [];
  foreach ($cat['steps'] as $i => $step) {
    $steps_json[] = '{"@type":"HowToStep","position":' . ($i+1) . ',"name":' . json_encode($step[1]) . ',"text":' . json_encode($step[3]) . '}';
  }
?>
{
  "@context":"https://schema.org",
  "@type":"HowTo",
  "name":<?= json_encode('How to Use ' . $cat['label'] . ' Prompts on Arigato Devan') ?>,
  "description":<?= json_encode($cat['tag_en']) ?>,
  "image":"https://arigatodevan.com/favicon/android-chrome-512x512.png",
  "step":[<?= implode(',', $steps_json) ?>]
}<?= $key !== array_key_last($cats) ? ',' : '' ?>
<?php endforeach; ?>
]
</script>
    <style>
        html, body { background: transparent !important; height: 100%; margin: 0; }
        body::before { content: ''; position: fixed; inset: 0; z-index: -2; background-image: url('backgroundwally/only-homepage-pic.webp'); background-size: cover; background-position: center top; background-repeat: no-repeat; filter: blur(3px) brightness(0.9); transform: scale(1.05); }
        body::after { content: ''; position: fixed; inset: 0; z-index: -1; background: rgba(253,251,247,0.85); pointer-events: none; }
        @media (max-width: 640px) { body::before { background-image: url('backgroundwally/only-homepage-pic-for-mobile.webp'); background-position: center center; } }
        .aurora-bg { display: none !important; }
    </style>
</head>
<body>
<header>
  <div class="logo-area" style="cursor:pointer" onclick="location.href='index.php'">
    <div class="logo-flipper"><div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo" id="profile-logo"></div><div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div></div>
    <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
  </div>
  <nav class="nav-links">
    <a href="digital_store/index.php" class="shop-nav-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg> SHOP</a>
    <a href="gallery.php">GALLERY</a>
    <a href="blogs.php">BLOGS</a>
    <a href="how_to_use.php" style="background:var(--primary-color);border:2px solid var(--text-color);border-radius:20px;box-shadow:3px 3px 0 var(--text-color);padding:6px 14px;"><i class="fa-solid fa-book-open"></i> GUIDE</a>
  </nav>
  <div class="header-right">
    <div class="header-divider"></div>
    <?php if ($is_li): ?>
      <a href="profile.php"><?= renderAvatar($_SESSION["profile_image"] ?? "", "admin-avatar", "User") ?></a>
    <?php else: ?>
      <a href="login.php" class="comic-btn" style="padding:8px 18px;text-decoration:none;font-size:.85rem;">LOGIN</a>
    <?php endif; ?>
  </div>
</header>

<div class="htu-page">

  <!-- -- Hero -- -->
  <div class="htu-hero">
    <div class="htu-hero-badge"><i class="fa-solid fa-book-open"></i> Complete Guide</div>
    <h1>How to Use<br><span class="highlight">Arigato Devan</span></h1>
    <p>Step-by-step guide for unlocking and using every type of AI prompt.</p>
    <button class="htu-lang-btn" id="lang-btn" onclick="toggleLang()">
      <i class="fa-solid fa-language"></i> <span id="lang-btn-text">Switch to Hinglish</span>
    </button>
  </div>

  <!-- -- Sticky Tabs -- -->
  <div class="htu-tabs-bar">
    <div class="htu-tabs" id="htu-tabs">
      <?php foreach ($cats as $key => $cat): ?>
      <button class="htu-tab<?= $key === $active ? ' active' : '' ?>" data-cat="<?= $key ?>" onclick="switchCat('<?= $key ?>')">
        <i class="<?= $cat['icon'] ?>"></i> <?= $cat['label'] ?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- -- Panels -- -->
  <?php foreach ($cats as $key => $cat): ?>
  <div class="htu-panel<?= $key === $active ? ' active' : '' ?>" id="panel-<?= $key ?>">

    <!-- Category header -->
    <div class="htu-cat-head">
      <div class="htu-cat-accent" style="background:<?= $cat['accent'] ?>;"></div>
      <div class="htu-cat-info">
        <div class="htu-cat-badge" style="background:<?= $cat['accent'] ?>22;color:var(--text-color);">
          <i class="<?= $cat['icon'] ?>"></i> <?= $cat['label'] ?>
        </div>
        <p class="htu-cat-tagline en"><?= htmlspecialchars($cat['tag_en']) ?></p>
        <p class="htu-cat-tagline hi htu-hidden"><?= htmlspecialchars($cat['tag_hi']) ?></p>
        <a href="<?= $cat['link'] ?>" class="htu-cat-link">
          <i class="<?= $cat['icon'] ?>"></i>
          <span class="en">Go to <?= $cat['label'] ?></span>
          <span class="hi htu-hidden"><?= $cat['label'] ?> pe Jao</span>
        </a>
      </div>
    </div>

    <!-- Steps -->
    <div class="htu-steps-grid">
      <?php foreach ($cat['steps'] as $i => [$icon, $ten, $thi, $den, $dhi, $tipen, $tiphi]): ?>
      <div class="htu-step-card" style="--htu-delay:<?= $i * .08 ?>s;border-top:4px solid <?= $cat['accent'] ?>;">
        <div class="htu-step-num"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></div>
        <div class="htu-step-icon" style="background:<?= $cat['accent'] ?>22;">
          <i class="<?= $icon ?>"></i>
        </div>
        <div class="htu-step-card-body">
          <div class="htu-step-title en"><?= htmlspecialchars($ten) ?></div>
          <div class="htu-step-title hi htu-hidden"><?= htmlspecialchars($thi) ?></div>
          <div class="htu-step-desc en"><?= htmlspecialchars($den) ?></div>
          <div class="htu-step-desc hi htu-hidden"><?= htmlspecialchars($dhi) ?></div>
          <?php if ($tipen): ?>
          <div class="htu-step-tip en"><i class="fa-solid fa-lightbulb"></i><?= htmlspecialchars($tipen) ?></div>
          <div class="htu-step-tip hi htu-hidden"><i class="fa-solid fa-lightbulb"></i><?= htmlspecialchars($tiphi) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
  <?php endforeach; ?>

</div><!-- .htu-page -->

<script>
// --- Tab switch ---------------------------------------------------------------
function switchCat(key) {
  document.querySelectorAll('.htu-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.htu-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('panel-' + key).classList.add('active');
  document.querySelector('[data-cat="' + key + '"]').classList.add('active');
  history.replaceState(null, '', '?cat=' + key);
  init3D();
}

// --- Language toggle ----------------------------------------------------------
let isHindi = false;
function toggleLang() {
  isHindi = !isHindi;
  document.querySelectorAll('.en').forEach(el => el.classList.toggle('htu-hidden', isHindi));
  document.querySelectorAll('.hi').forEach(el => el.classList.toggle('htu-hidden', !isHindi));
  document.getElementById('lang-btn-text').textContent = isHindi ? 'Switch to English' : 'Switch to Hinglish';
}

// --- 3D tilt (desktop only) ---------------------------------------------------
function init3D() {
  if (window.innerWidth < 700) return;
  document.querySelectorAll('.htu-panel.active .htu-step-card').forEach(card => {
    card.onmousemove = e => {
      const r  = card.getBoundingClientRect();
      const x  = (e.clientX - r.left  - r.width  / 2) / (r.width  / 2);
      const y  = (e.clientY - r.top   - r.height / 2) / (r.height / 2);
      card.style.transform = `perspective(600px) rotateX(${-y * 9}deg) rotateY(${x * 9}deg) scale(1.04)`;
      card.style.boxShadow = `${x * -8 + 5}px ${y * -8 + 5}px 0 #2d2a35`;
    };
    card.onmouseleave = () => {
      card.style.transform = '';
      card.style.boxShadow = '';
    };
  });
}
init3D();

// --- Hash / URL param on load -------------------------------------------------
(function() {
  const p = new URLSearchParams(location.search).get('cat');
  if (p && p !== '<?= $active ?>') switchCat(p);
})();
</script>

<?php include 'footer.php'; ?>

</body>
</html>


