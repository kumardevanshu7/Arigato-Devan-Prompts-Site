<?php
// Set $_steps_page before including: 'secret_code' | 'insta_viral' | 'unreleased' | 'already_uploaded'
$_sp  = $_steps_page ?? 'generic';
$_li  = isset($_SESSION['user_id']);

$_all_steps = [
    'secret_code' => [
        ['🎬', 'Watch Reel',     'Get the secret code'],
        ['📝', 'Enter Code',     'Paste in card box'],
        ['🔓', 'Unlock!',        'Prompt revealed'],
        ['📋', 'Copy Prompt',    ''],
        ['🤖', 'Paste on Gemini','& Generate! ✨'],
    ],
    'insta_viral' => [
        ['🧮', 'Solve Math',     'Answer the challenge'],
        ['🔓', 'Unlock!',        'Prompt revealed'],
        ['📋', 'Copy Prompt',    ''],
        ['🤖', 'Paste on Gemini','& Generate! ✨'],
    ],
    'unreleased' => [
        ['❤️', $_li ? '20 Taps' : '90 Taps', 'Tap the Love Bar'],
        ['🔓', 'Unlock!',        'Prompt revealed'],
        ['📋', 'Copy Prompt',    ''],
        ['🤖', 'Paste on Gemini','& Generate! ✨'],
    ],
    'already_uploaded' => [
        ['👆', 'Just 9 Taps',   'Tap to unlock'],
        ['🔓', 'Unlock!',        'Prompt revealed'],
        ['📋', 'Copy Prompt',    ''],
        ['🤖', 'Paste on Gemini','& Generate! ✨'],
    ],
    'generic' => [
        ['🔓', 'Unlock Prompt',  ''],
        ['📋', 'Copy Prompt',    ''],
        ['🤖', 'Paste on Gemini','& Generate! ✨'],
    ],
];
$_steps = $_all_steps[$_sp] ?? $_all_steps['generic'];
$_last  = count($_steps) - 1;
?>
<style>
.sg-outer{overflow-x:auto;margin-bottom:28px;padding-bottom:4px;scrollbar-width:none;}
.sg-outer::-webkit-scrollbar{display:none;}
.sg-wrap{display:flex;align-items:center;gap:0;width:max-content;padding:8px 2px 12px;}
.sg-box{flex-shrink:0;position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:16px;padding:12px 14px 10px;min-width:96px;max-width:116px;text-align:center;box-shadow:4px 4px 0 var(--text-color);transition:transform .18s,box-shadow .18s;cursor:default;}
.sg-box:hover{transform:translateY(-4px);box-shadow:4px 8px 0 var(--text-color);}
.sg-box.sg-last{background:var(--primary-color);}
.sg-badge{position:absolute;top:-11px;left:-9px;width:22px;height:22px;background:var(--text-color);color:var(--bg-color);border-radius:50%;font-size:.62rem;font-weight:900;display:flex;align-items:center;justify-content:center;font-family:var(--font-main);border:2px solid var(--bg-color);}
.sg-icon{font-size:1.5rem;line-height:1;margin-bottom:6px;}
.sg-title{font-size:.7rem;font-weight:900;color:var(--text-color);font-family:var(--font-main);text-transform:uppercase;letter-spacing:.4px;line-height:1.25;}
.sg-sub{font-size:.6rem;color:#888;font-weight:600;margin-top:3px;line-height:1.2;}
.sg-last .sg-title,.sg-last .sg-sub{color:var(--text-color);}
.sg-arr{flex-shrink:0;display:flex;align-items:center;padding:0 3px;margin-top:-2px;}
.sg-arr svg{display:block;}
</style>
<div class="sg-outer">
  <div class="sg-wrap">
    <?php foreach ($_steps as $i => [$icon, $title, $sub]): ?>
      <?php if ($i > 0): ?>
      <div class="sg-arr">
        <svg width="28" height="18" viewBox="0 0 28 18" fill="none">
          <path d="M2 9 H22" stroke="#2d2a35" stroke-width="2.5" stroke-linecap="round"/>
          <path d="M17 3 L24 9 L17 15" stroke="#2d2a35" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <?php endif; ?>
      <div class="sg-box<?= $i === $_last ? ' sg-last' : '' ?>">
        <div class="sg-badge"><?= $i+1 ?></div>
        <div class="sg-icon"><?= $icon ?></div>
        <div class="sg-title"><?= htmlspecialchars($title) ?></div>
        <?php if ($sub): ?><div class="sg-sub"><?= htmlspecialchars($sub) ?></div><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
