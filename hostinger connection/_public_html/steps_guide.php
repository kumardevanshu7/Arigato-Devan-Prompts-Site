<?php
// Set $_steps_page before including: 'secret_code' | 'insta_viral' | 'unreleased' | 'already_uploaded'
$_sp = $_steps_page ?? 'generic';
$_li = isset($_SESSION['user_id']);

// Each step: [ fa-icon-class, title, subtitle ]
$_all_steps = [
    'secret_code' => [
        ['fa-solid fa-play',               'Watch Reel',      'Get the secret code'],
        ['fa-solid fa-keyboard',           'Enter Code',      'Paste in the box'],
        ['fa-solid fa-lock-open',          'Unlock',          'Prompt revealed'],
        ['fa-solid fa-copy',               'Copy Prompt',     ''],
        ['fa-solid fa-wand-magic-sparkles','Paste on Gemini', 'Generate!'],
    ],
    'insta_viral' => [
        ['fa-solid fa-calculator',         'Solve Math',      'Answer the challenge'],
        ['fa-solid fa-lock-open',          'Unlock',          'Prompt revealed'],
        ['fa-solid fa-copy',               'Copy Prompt',     ''],
        ['fa-solid fa-wand-magic-sparkles','Paste on Gemini', 'Generate!'],
    ],
    'unreleased' => [
        ['fa-solid fa-heart',              $_li ? '20 Taps' : '90 Taps', 'Tap the Love Bar'],
        ['fa-solid fa-lock-open',          'Unlock',          'Prompt revealed'],
        ['fa-solid fa-copy',               'Copy Prompt',     ''],
        ['fa-solid fa-wand-magic-sparkles','Paste on Gemini', 'Generate!'],
    ],
    'already_uploaded' => [
        ['fa-solid fa-hand-pointer',       'Just 9 Taps',     'Tap to unlock'],
        ['fa-solid fa-lock-open',          'Unlock',          'Prompt revealed'],
        ['fa-solid fa-copy',               'Copy Prompt',     ''],
        ['fa-solid fa-wand-magic-sparkles','Paste on Gemini', 'Generate!'],
    ],
    'homepage' => [
        ['fa-solid fa-layer-group',        'Pick Category',   'Secret / Viral / More'],
        ['fa-solid fa-lock-open',          'Unlock Prompt',   'Easy challenges'],
        ['fa-solid fa-copy',               'Copy Prompt',     ''],
        ['fa-solid fa-wand-magic-sparkles','Paste on Gemini', 'Generate!'],
    ],
    'generic' => [
        ['fa-solid fa-lock-open',          'Unlock',          ''],
        ['fa-solid fa-copy',               'Copy Prompt',     ''],
        ['fa-solid fa-wand-magic-sparkles','Paste on Gemini', 'Generate!'],
    ],
];
$_steps = $_all_steps[$_sp] ?? $_all_steps['generic'];
$_last  = count($_steps) - 1;
?>
<style>
.sg-outer{overflow-x:auto;margin-bottom:28px;padding:14px 14px 6px;scrollbar-width:none;text-align:center;width:100%;box-sizing:border-box;max-width:100%;overscroll-behavior-x:contain;}
.sg-outer::-webkit-scrollbar{display:none;}
.sg-wrap{display:inline-flex;align-items:center;gap:0;padding:0 2px 8px;}
.sg-box{flex-shrink:0;position:relative;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:18px;padding:14px 16px 12px;width:110px;text-align:center;box-shadow:4px 4px 0 var(--text-color);transition:transform .18s,box-shadow .18s;}
.sg-box:hover{transform:translateY(-4px);box-shadow:4px 8px 0 var(--text-color);}
.sg-box.sg-last{background:var(--primary-color);}
.sg-badge{position:absolute;top:-12px;left:-10px;width:24px;height:24px;background:var(--text-color);color:var(--bg-color);border-radius:50%;font-size:.65rem;font-weight:900;display:flex;align-items:center;justify-content:center;font-family:var(--font-main);border:2.5px solid var(--bg-color);line-height:1;z-index:1;}
.sg-icon-wrap{width:40px;height:40px;border-radius:12px;background:var(--secondary-color);border:2px solid var(--text-color);display:flex;align-items:center;justify-content:center;margin-bottom:8px;flex-shrink:0;}
.sg-last .sg-icon-wrap{background:var(--card-bg);}
.sg-icon-wrap i{font-size:.95rem;color:var(--text-color);}
.sg-title{font-size:.68rem;font-weight:900;color:var(--text-color);font-family:var(--font-main);text-transform:uppercase;letter-spacing:.5px;line-height:1.25;}
.sg-sub{font-size:.58rem;color:#777;font-weight:700;margin-top:4px;line-height:1.25;font-family:var(--font-main);}
.sg-last .sg-sub{color:#555;}
.sg-arr{flex-shrink:0;display:flex;align-items:center;padding:0 4px;margin-top:-6px;}
.sg-detail-btn{display:inline-flex;align-items:center;gap:7px;padding:8px 20px;background:var(--card-bg);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:40px;font-family:var(--font-main);font-weight:900;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;text-decoration:none;box-shadow:3px 3px 0 var(--text-color);transition:all .18s;margin-top:4px;max-width:calc(100vw - 40px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sg-detail-btn:hover{transform:translateY(-2px);box-shadow:3px 5px 0 var(--text-color);background:var(--secondary-color);}
@keyframes sg-pop{from{opacity:0;transform:scale(.85) translateY(10px);}to{opacity:1;transform:scale(1) translateY(0);}}
@media(max-width:540px){.sg-box{width:68px;padding:10px 6px 10px;border-radius:14px;}.sg-arr{padding:0 2px;}.sg-arr svg{width:16px;height:12px;}.sg-icon-wrap{width:32px;height:32px;border-radius:10px;}.sg-icon-wrap i{font-size:.78rem;}.sg-title{font-size:.6rem;letter-spacing:0;}.sg-sub{font-size:.54rem;}}
@media(max-width:390px){.sg-box{width:60px;padding:8px 4px 8px;border-radius:12px;}.sg-arr svg{width:13px;height:10px;}.sg-icon-wrap{width:28px;height:28px;}.sg-icon-wrap i{font-size:.68rem;}.sg-title{font-size:.55rem;}.sg-badge{width:20px;height:20px;font-size:.55rem;}}
</style>
<div class="sg-outer">
  <div class="sg-wrap">
    <?php foreach ($_steps as $i => [$faicon, $title, $sub]): ?>
      <?php if ($i > 0): ?>
      <div class="sg-arr">
        <svg width="30" height="16" viewBox="0 0 30 16" fill="none">
          <path d="M2 8H24" stroke="#2d2a35" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M19 2L26 8L19 14" stroke="#2d2a35" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <?php endif; ?>
      <div class="sg-box<?= $i === $_last ? ' sg-last' : '' ?>" style="animation:sg-pop .38s ease both;animation-delay:<?= $i * .08 ?>s;">
        <div class="sg-badge"><?= $i + 1 ?></div>
        <div class="sg-icon-wrap"><i class="<?= $faicon ?>"></i></div>
        <div class="sg-title"><?= htmlspecialchars($title) ?></div>
        <?php if ($sub): ?><div class="sg-sub"><?= htmlspecialchars($sub) ?></div><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
