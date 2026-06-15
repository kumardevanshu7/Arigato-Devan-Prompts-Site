<?php
session_start();
require_once "db.php";

// Admin only
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] = "Access denied.";
    header("Location: index.php");
    exit();
}

// Flash messages
$success = $_SESSION["success_msg"] ?? "";
$error   = $_SESSION["error_msg"] ?? "";
unset($_SESSION["success_msg"], $_SESSION["error_msg"]);

// Fetch all blogs with author name
$blogs_all = $pdo->query("
    SELECT b.*, u.username as author_name, u.avatar as author_avatar_ob, u.profile_image as author_google_img
    FROM blogs b
    LEFT JOIN users u ON b.author_id = u.id
    ORDER BY b.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$blog_count  = count($blogs_all);
$pub_count   = count(array_filter($blogs_all, fn($b) => $b["is_published"]));
$draft_count = $blog_count - $pub_count;
$total_likes = array_sum(array_column($blogs_all, "likes_count"));

function getAuthorAvatar(array $row): string {
    if (!empty($row["author_avatar_ob"]))  return $row["author_avatar_ob"];
    if (!empty($row["author_google_img"])) return $row["author_google_img"];
    return "https://api.dicebear.com/7.x/avataaars/svg?seed=admin";
}

$admin_info = $pdo->query("SELECT username FROM users WHERE role='admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$admin_name = $admin_info['username'] ?? 'Admin';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Blog Management — Admin | Arigato Devan</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once "gtag.php"; ?>
<style>
:root{
  --bg:#07060f;--surface:#0f0d1e;--surface2:#15122a;
  --border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);
  --accent:#8b5cf6;--accent2:#c084fc;
  --pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;
  --yellow:#fbbf24;--orange:#fb923c;--red:#f87171;
  --text:#e2e0ff;--muted:#9490bb;
  --font:'Inter',sans-serif;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:var(--font);overflow-x:hidden;min-height:100vh}

/* ── SCROLL BAR ── */
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}

/* ── PROGRESS BAR ── */
#sp{position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--pink),var(--cyan));z-index:9999;transition:width .1s;box-shadow:0 0 10px var(--accent);width:0}
#pc{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.5}

/* ══════════════════════════════════
   DESKTOP SIDEBAR
══════════════════════════════════ */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:220px;background:rgba(7,6,15,0.98);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column;transition:transform .3s}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid var(--border2)}
.sb-brand{font-size:.72rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:flex;align-items:center;gap:8px}
.sb-brand i{-webkit-text-fill-color:#a78bfa;font-size:1rem}
.sb-admin{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border2)}
.sb-av{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--accent);flex-shrink:0}
.sb-av-ph{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#fff;flex-shrink:0}
.sb-uname{font-size:.78rem;font-weight:800;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sb-role{font-size:.6rem;font-weight:700;color:var(--accent2);text-transform:uppercase;letter-spacing:.1em}
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px}
.sb-nav::-webkit-scrollbar{width:2px}
.sb-sec{font-size:.58rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 10px 5px}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;border:1px solid transparent;margin-bottom:1px}
.sb-link:hover{background:rgba(139,92,246,0.08);color:var(--text)}
.sb-link.active{background:rgba(139,92,246,0.15);color:var(--accent2);border-color:var(--border)}
.sb-link i{width:16px;text-align:center;flex-shrink:0}
.sb-bottom{padding:12px 8px;border-top:1px solid var(--border2)}
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:700;color:var(--red);text-decoration:none;transition:all .2s}
.sb-logout:hover{background:rgba(248,113,113,0.1)}

/* ══════════════════════════════════
   MOBILE DRAWER (slide-in)
══════════════════════════════════ */
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);z-index:500}
.drawer{position:fixed;left:0;top:0;bottom:0;width:260px;background:rgba(7,6,15,0.99);border-right:1px solid var(--border);z-index:600;display:flex;flex-direction:column;transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1)}
.drawer.open{transform:translateX(0)}
.drawer-overlay.open{display:block}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:18px 16px;border-bottom:1px solid var(--border2)}
.drawer-brand{font-size:.8rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.drawer-close{width:32px;height:32px;border-radius:8px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:var(--red);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem}
.drawer-user{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border2)}
.d-av{width:40px;height:40px;border-radius:50%;border:2px solid var(--accent);object-fit:cover;flex-shrink:0}
.d-av-ph{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;flex-shrink:0}
.d-uname{font-size:.85rem;font-weight:800}
.d-role{font-size:.65rem;color:var(--accent2);font-weight:700;text-transform:uppercase}
.drawer-nav{flex:1;overflow-y:auto;padding:10px 10px}
.d-sec{font-size:.6rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 8px 5px}
.d-link{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;margin-bottom:2px}
.d-link:hover{background:rgba(139,92,246,0.08);color:var(--text)}
.d-link.active{background:rgba(139,92,246,0.15);color:var(--accent2)}
.d-link i{width:18px;text-align:center}
.drawer-bottom{padding:12px 10px;border-top:1px solid var(--border2)}
.d-logout{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:700;color:var(--red);text-decoration:none}
.d-logout:hover{background:rgba(248,113,113,0.08)}

/* ══════════════════════════════════
   MOBILE TOP BAR
══════════════════════════════════ */
.mob-topbar{display:none;position:sticky;top:0;z-index:100;background:rgba(7,6,15,0.95);backdrop-filter:blur(16px);border-bottom:1px solid var(--border2);padding:14px 16px;align-items:center;gap:12px}
.mob-menu-btn{width:38px;height:38px;border-radius:10px;background:rgba(139,92,246,0.08);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--accent2);font-size:1rem;cursor:pointer;flex-shrink:0}
.mob-page-title{font-size:1rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
.mob-new-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;font-size:.78rem;font-weight:800;text-decoration:none;background:rgba(139,92,246,0.12);color:var(--accent2);border:1px solid rgba(139,92,246,0.28);flex-shrink:0}

/* ══════════════════════════════════
   MAIN CONTENT
══════════════════════════════════ */
.main{margin-left:220px;min-height:100vh;padding:28px 32px 80px;position:relative;z-index:1}

/* TOPBAR (desktop) */
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:24px;flex-wrap:wrap}
.tb-title{font-size:1.5rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1;display:flex;align-items:center;gap:10px}
.tb-title i{-webkit-text-fill-color:var(--pink);font-size:1.3rem}
.tb-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:.78rem;font-weight:800;text-decoration:none;border:1px solid;transition:all .2s;cursor:pointer;font-family:var(--font)}
.tb-pink{background:rgba(244,114,182,0.08);color:var(--pink);border-color:rgba(244,114,182,0.25)}
.tb-pink:hover{background:rgba(244,114,182,0.15);transform:translateY(-2px)}
.tb-purple{background:rgba(139,92,246,0.1);color:var(--accent2);border-color:rgba(139,92,246,0.28)}
.tb-purple:hover{background:rgba(139,92,246,0.18);transform:translateY(-2px)}

/* FLASH */
.flash{padding:13px 18px;border-radius:12px;font-weight:700;font-size:.83rem;margin-bottom:18px;display:flex;align-items:center;gap:10px;border:1px solid}
.flash-ok{background:rgba(74,222,128,0.07);color:var(--green);border-color:rgba(74,222,128,0.2)}
.flash-err{background:rgba(248,113,113,0.07);color:var(--red);border-color:rgba(248,113,113,0.2)}

/* STAT PILLS */
.stat-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px}
.stat-pill{display:flex;align-items:center;gap:10px;padding:14px 20px;border-radius:14px;border:1px solid;backdrop-filter:blur(8px);transition:all .25s;font-size:.82rem;font-weight:700}
.stat-pill:hover{transform:translateY(-2px)}
.sp-pub{background:rgba(74,222,128,0.07);border-color:rgba(74,222,128,0.2);color:var(--green)}
.sp-draft{background:rgba(251,191,36,0.07);border-color:rgba(251,191,36,0.2);color:var(--yellow)}
.sp-likes{background:rgba(244,114,182,0.07);border-color:rgba(244,114,182,0.2);color:var(--pink)}
.sp-num{font-size:1.4rem;font-weight:900;line-height:1}

/* CARD */
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:18px;padding:22px;backdrop-filter:blur(8px)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border2);flex-wrap:wrap;gap:10px}
.card-title{font-size:.95rem;font-weight:900;display:flex;align-items:center;gap:9px}
.card-title i{color:var(--accent2)}
.total-badge{background:rgba(139,92,246,0.12);border:1px solid var(--border);border-radius:100px;padding:4px 14px;font-size:.72rem;font-weight:900;color:var(--accent2)}

/* SEARCH */
.search-wrap{position:relative;margin-bottom:16px}
.search-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.8rem;pointer-events:none}
.bm-search{width:100%;padding:11px 16px 11px 40px;border:1px solid var(--border);border-radius:12px;font-family:var(--font);font-weight:600;font-size:.88rem;background:rgba(15,13,30,0.8);color:var(--text);outline:none;transition:all .2s;box-sizing:border-box}
.bm-search:focus{border-color:rgba(139,92,246,0.5);box-shadow:0 0 0 3px rgba(139,92,246,0.1)}
.bm-search::placeholder{color:var(--muted)}

/* ── DESKTOP BLOG ITEM ── */
.blog-item{display:flex;align-items:center;gap:14px;padding:14px 16px;background:rgba(0,0,0,0.2);border:1px solid var(--border2);border-radius:14px;margin-bottom:10px;transition:all .22s}
.blog-item:hover{border-color:rgba(139,92,246,0.3);background:rgba(139,92,246,0.04);transform:translateX(3px)}
.blog-item.is-draft{border-style:dashed;background:rgba(251,191,36,0.03)}
.blog-thumb{width:56px;height:56px;border-radius:10px;object-fit:cover;border:1px solid var(--border);flex-shrink:0}
.blog-thumb-ph{width:56px;height:56px;border-radius:10px;border:1px solid var(--border);background:rgba(139,92,246,0.12);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--accent2);flex-shrink:0}
.blog-details{flex-grow:1;min-width:0}
.blog-title-row{display:flex;align-items:center;gap:8px;margin-bottom:5px;flex-wrap:wrap}
.blog-title{font-weight:800;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px}
.status-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:100px;font-size:.63rem;font-weight:900;text-transform:uppercase;letter-spacing:.05em;border:1px solid;flex-shrink:0}
.sb-pub{background:rgba(74,222,128,0.1);color:var(--green);border-color:rgba(74,222,128,0.25)}
.sb-draft{background:rgba(251,191,36,0.08);color:var(--yellow);border-color:rgba(251,191,36,0.2)}
.blog-meta{font-size:.75rem;color:var(--muted);font-weight:600;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.author-av{width:18px;height:18px;border-radius:50%;object-fit:cover;border:1px solid rgba(139,92,246,0.4);flex-shrink:0}
.blog-tag{background:rgba(139,92,246,0.08);border:1px solid var(--border2);border-radius:8px;padding:1px 8px;font-size:.68rem;color:var(--accent2)}
.view-lnk{color:var(--accent);font-weight:800;text-decoration:none;display:flex;align-items:center;gap:3px}
.view-lnk:hover{color:var(--accent2)}

/* DESKTOP ACTION BUTTONS */
.action-btns{display:flex;gap:7px;flex-shrink:0}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:9px;font-size:.85rem;border:1px solid;transition:all .2s;cursor:pointer;text-decoration:none;background:transparent;font-family:var(--font)}
.act-edit{color:var(--accent2);border-color:rgba(139,92,246,0.25);background:rgba(139,92,246,0.08)}
.act-edit:hover{background:rgba(139,92,246,0.18);transform:translateY(-2px)}
.act-pub{color:var(--green);border-color:rgba(74,222,128,0.25);background:rgba(74,222,128,0.06)}
.act-pub:hover{background:rgba(74,222,128,0.14);transform:translateY(-2px)}
.act-unpub{color:var(--yellow);border-color:rgba(251,191,36,0.25);background:rgba(251,191,36,0.06)}
.act-unpub:hover{background:rgba(251,191,36,0.14);transform:translateY(-2px)}
.act-del{color:var(--red);border-color:rgba(248,113,113,0.25);background:rgba(248,113,113,0.06)}
.act-del:hover{background:rgba(248,113,113,0.16);transform:translateY(-2px)}

/* ══════════════════════════════════
   MOBILE BLOG CARD
══════════════════════════════════ */
.mob-blog-card{display:none;background:rgba(15,13,30,0.8);border:1px solid var(--border2);border-radius:16px;padding:14px;margin-bottom:12px;transition:all .22s;position:relative}
.mob-blog-card:hover{border-color:rgba(139,92,246,0.3)}
.mob-card-top{display:flex;align-items:center;gap:12px}
.mob-thumb{width:52px;height:52px;border-radius:10px;object-fit:cover;border:1px solid var(--border);flex-shrink:0}
.mob-thumb-ph{width:52px;height:52px;border-radius:10px;background:rgba(139,92,246,0.12);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--accent2);flex-shrink:0}
.mob-card-info{flex:1;min-width:0}
.mob-title{font-size:.9rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:4px}
.mob-badges{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:4px}
.mob-meta{font-size:.72rem;color:var(--muted);display:flex;align-items:center;gap:5px}
/* Three-dot button */
.mob-more-btn{width:36px;height:36px;border-radius:10px;background:rgba(139,92,246,0.08);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--accent2);cursor:pointer;flex-shrink:0;font-size:1rem;position:relative}
/* Dropdown menu */
.mob-dropdown{display:none;position:absolute;right:14px;top:54px;background:rgba(10,8,22,0.98);border:1px solid var(--border);border-radius:14px;overflow:hidden;z-index:50;min-width:180px;box-shadow:0 12px 40px rgba(0,0,0,0.6);backdrop-filter:blur(20px)}
.mob-dropdown.open{display:block;animation:dropIn .18s ease}
@keyframes dropIn{from{opacity:0;transform:translateY(-8px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.mob-dd-item{display:flex;align-items:center;gap:11px;padding:13px 16px;font-size:.85rem;font-weight:700;text-decoration:none;transition:background .15s;border-bottom:1px solid var(--border2)}
.mob-dd-item:last-child{border-bottom:none}
.mob-dd-item:hover{background:rgba(139,92,246,0.08)}
.mob-dd-item i{width:18px;text-align:center;font-size:.85rem}
.dd-edit{color:var(--accent2)}
.dd-pub{color:var(--green)}
.dd-unpub{color:var(--yellow)}
.dd-view{color:var(--cyan)}
.dd-del{color:var(--red)}
/* Draft stripe */
.mob-blog-card.is-draft{border-style:dashed;border-color:rgba(251,191,36,0.2)}

/* ══════════════════════════════════
   MOBILE BOTTOM NAV
══════════════════════════════════ */
.mob-bottom-nav{display:none;position:fixed;bottom:0;left:0;right:0;z-index:400;background:rgba(7,6,15,0.96);backdrop-filter:blur(20px);border-top:1px solid var(--border);padding:8px 0 env(safe-area-inset-bottom,8px)}
.mob-nav-items{display:flex;justify-content:space-around;align-items:center}
.mob-nav-item{display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 12px;border-radius:12px;text-decoration:none;color:var(--muted);font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;transition:all .2s;border:1px solid transparent}
.mob-nav-item i{font-size:1.1rem}
.mob-nav-item.active{color:var(--accent2);background:rgba(139,92,246,0.1);border-color:var(--border)}
.mob-nav-item:hover{color:var(--text)}

/* ── EMPTY STATE ── */
.empty-state{text-align:center;padding:60px 20px;color:var(--muted)}
.empty-state i{font-size:2.5rem;margin-bottom:12px;display:block;color:rgba(139,92,246,0.3)}
.empty-state p{font-weight:700;margin-bottom:12px}
.empty-state a{color:var(--accent2);font-weight:800;text-decoration:none}

/* ── DELETE MODAL ── */
.modal-ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(14px);z-index:2000;align-items:center;justify-content:center;padding:20px}
.modal-box{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px;max-width:400px;width:100%;box-shadow:0 24px 80px rgba(0,0,0,0.6);text-align:center}
.del-icon{width:56px;height:56px;border-radius:16px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.22);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--red);margin:0 auto 14px}
.del-btns{display:flex;gap:10px;margin-top:18px}
.del-cancel{flex:1;padding:11px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:11px;color:var(--muted);font-weight:800;cursor:pointer;font-family:var(--font);font-size:.85rem;transition:all .2s}
.del-cancel:hover{border-color:var(--accent);color:var(--text)}
.del-confirm{flex:1;padding:11px;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.25);border-radius:11px;color:var(--red);font-weight:800;cursor:pointer;font-family:var(--font);font-size:.85rem;transition:all .2s;width:100%}
.del-confirm:hover{background:rgba(248,113,113,0.2)}

/* ══════════════════════════════════
   CUSTOM CURSOR (desktop only)
══════════════════════════════════ */
@media(min-width:769px){
  *{cursor:none!important}
  #c-dot{position:fixed;width:8px;height:8px;background:#c084fc;border-radius:50%;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width .15s,height .15s,background .2s;box-shadow:0 0 8px #c084fc,0 0 16px rgba(192,132,252,0.4)}
  #c-ring{position:fixed;width:32px;height:32px;border:1.5px solid rgba(139,92,246,0.6);border-radius:50%;pointer-events:none;z-index:99998;transform:translate(-50%,-50%);transition:width .2s,height .2s,border-color .2s;box-shadow:0 0 10px rgba(139,92,246,0.2)}
  .c-hover #c-dot{width:12px;height:12px;background:#f472b6;box-shadow:0 0 12px #f472b6,0 0 24px rgba(244,114,182,0.5)}
  .c-hover #c-ring{width:44px;height:44px;border-color:rgba(244,114,182,0.5)}
  .c-click #c-dot{width:6px;height:6px;background:#22d3ee}
  .c-click #c-ring{width:24px;height:24px;border-color:rgba(34,211,238,0.7)}
}

/* ══════════════════════════════════
   RESPONSIVE BREAKPOINTS
══════════════════════════════════ */
@media(max-width:900px){
  .sidebar{width:58px}
  .sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}
  .sb-admin{padding:10px;justify-content:center}
  .sb-link{padding:10px;justify-content:center}
  .main{margin-left:58px}
}

@media(max-width:768px){
  #c-dot,#c-ring{display:none!important}
  /* Hide desktop sidebar completely */
  .sidebar{display:none}
  /* Show mobile top bar */
  .mob-topbar{display:flex}
  /* Adjust main for mobile */
  .main{margin-left:0;padding:16px 14px 90px}
  /* Hide desktop blog items, show mobile cards */
  .blog-item{display:none}
  .mob-blog-card{display:block}
  /* Hide desktop topbar, stat pills are still shown */
  .topbar{display:none}
  /* Show bottom nav */
  .mob-bottom-nav{display:block}
  /* Stat pills: horizontal scroll on mobile */
  .stat-row{flex-wrap:nowrap;overflow-x:auto;padding-bottom:6px;-webkit-overflow-scrolling:touch}
  .stat-row::-webkit-scrollbar{height:2px}
  .stat-pill{flex-shrink:0;padding:12px 16px}
  /* Card padding tighter */
  .card{padding:16px;border-radius:14px}
}
body::before, body::after { display: none !important; background-image: none !important; }
</style>
</head>
<body>
<div id="c-dot"></div>
<div id="c-ring"></div>
<div id="sp"></div>
<canvas id="pc"></canvas>

<!-- ══ MOBILE DRAWER ══ -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="sideDrawer">
  <div class="drawer-head">
    <div class="drawer-brand">Arigato Admin</div>
    <div class="drawer-close" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></div>
  </div>
  <div class="drawer-user">
    <?php $sav=!empty($_SESSION['profile_image'])?htmlspecialchars($_SESSION['profile_image']):''; ?>
    <?php if($sav): ?><img src="<?= $sav ?>" class="d-av" alt="">
    <?php else: ?><div class="d-av-ph"><?= strtoupper(substr($admin_name,0,1)) ?></div><?php endif; ?>
    <div><div class="d-uname"><?= htmlspecialchars($admin_name) ?></div><div class="d-role">Admin</div></div>
  </div>
  <nav class="drawer-nav">
    <div class="d-sec">Overview</div>
    <a href="dashboard.php" class="d-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="analytics.php" class="d-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    <div class="d-sec">Content</div>
    <a href="upload_prompt.php" class="d-link"><i class="fa-solid fa-upload"></i> Upload Prompt</a>
    <a href="manage_prompts.php" class="d-link"><i class="fa-solid fa-list-check"></i> Manage Prompts</a>
    <a href="prompt_links.php" class="d-link"><i class="fa-solid fa-link"></i> Prompt Links</a>
    <a href="potd_manager.php" class="d-link"><i class="fa-solid fa-sun"></i> POTD Manager</a>
    <div class="d-sec">Blog</div>
    <a href="blog_admin.php" class="d-link active"><i class="fa-solid fa-pen-nib"></i> Blog Admin</a>
    <a href="blog_create.php" class="d-link"><i class="fa-solid fa-plus"></i> New Post</a>
    <div class="d-sec">Users</div>
    <a href="user_management.php" class="d-link"><i class="fa-solid fa-users"></i> Users</a>
    <div class="d-sec">Tools</div>
    <a href="index.php" class="d-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
  </nav>
  <div class="drawer-bottom">
    <a href="login.php?logout=1" class="d-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
</div>

<!-- ══ MOBILE TOP BAR ══ -->
<div class="mob-topbar">
  <div class="mob-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></div>
  <div class="mob-page-title"><i class="fa-solid fa-pen-nib" style="-webkit-text-fill-color:var(--pink);margin-right:6px"></i>Blog Admin</div>
  <a href="blog_create.php" class="mob-new-btn"><i class="fa-solid fa-plus"></i> New</a>
</div>

<!-- ══ DESKTOP SIDEBAR ══ -->
<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-brand"><i class="fa-solid fa-shield-halved"></i> <span>Arigato Admin</span></div>
  </div>
  <div class="sb-admin">
    <?php if($sav): ?><img src="<?= $sav ?>" class="sb-av" alt="">
    <?php else: ?><div class="sb-av-ph"><?= strtoupper(substr($admin_name,0,1)) ?></div><?php endif; ?>
    <div><div class="sb-uname"><?= htmlspecialchars($admin_name) ?></div><div class="sb-role">Admin</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <a href="analytics.php" class="sb-link"><i class="fa-solid fa-chart-line"></i> <span>Analytics</span></a>
    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="prompt_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
    <div class="sb-sec">Blog</div>
    <a href="blog_admin.php" class="sb-link active"><i class="fa-solid fa-pen-nib"></i> <span>Blog Admin</span></a>
    <a href="blog_create.php" class="sb-link"><i class="fa-solid fa-plus"></i> <span>New Post</span></a>
    <div class="sb-sec">Users</div>
    <a href="user_management.php" class="sb-link"><i class="fa-solid fa-users"></i> <span>Users</span></a>
    <div class="sb-sec">Tools</div>
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
  </nav>
  <div class="sb-bottom">
    <a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
  </div>
</aside>

<!-- ══ MAIN CONTENT ══ -->
<div class="main">

  <?php if($success): ?>
  <div class="flash flash-ok"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
  <?php endif; ?>
  <?php if($error): ?>
  <div class="flash flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div>
  <?php endif; ?>

  <!-- Desktop topbar -->
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-pen-nib"></i> Blog Management</div>
    <a href="blogs.php" target="_blank" class="tb-btn tb-pink"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Blog</a>
    <a href="blog_create.php" class="tb-btn tb-purple"><i class="fa-solid fa-plus"></i> New Post</a>
  </div>

  <!-- Stat pills -->
  <div class="stat-row">
    <div class="stat-pill sp-pub">
      <span class="sp-num"><?= $pub_count ?></span>
      <div><i class="fa-solid fa-rocket"></i> Published</div>
    </div>
    <div class="stat-pill sp-draft">
      <span class="sp-num"><?= $draft_count ?></span>
      <div><i class="fa-solid fa-file-pen"></i> Drafts</div>
    </div>
    <div class="stat-pill sp-likes">
      <span class="sp-num"><?= $total_likes ?></span>
      <div><i class="fa-solid fa-heart"></i> Likes</div>
    </div>
  </div>

  <!-- Blog list card -->
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-list"></i> All Blog Posts</div>
      <div class="total-badge"><?= $blog_count ?> Total</div>
    </div>

    <?php if($blog_count === 0): ?>
    <div class="empty-state">
      <i class="fa-solid fa-file-pen"></i>
      <p>No blog posts yet.</p>
      <a href="blog_create.php">Create your first post <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <?php else: ?>

    <div class="search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="blog-search" class="bm-search" placeholder="Search by title or tag...">
    </div>

    <div id="blog-items-list">
    <?php foreach($blogs_all as $bl):
      $authorAv = getAuthorAvatar($bl);
      $tagsArr  = $bl["tags"] ? array_filter(array_map("trim", explode(",", $bl["tags"]))) : [];
      $isPub    = (bool)$bl["is_published"];
    ?>

    <!-- ── DESKTOP ITEM ── -->
    <div class="blog-item <?= $isPub ? "" : "is-draft" ?>"
         data-title="<?= strtolower(htmlspecialchars($bl["title"])) ?>"
         data-tags="<?= strtolower(htmlspecialchars($bl["tags"] ?? "")) ?>">
      <?php if($bl["image_path"]): ?>
      <img loading="lazy" src="<?= htmlspecialchars($bl["image_path"]) ?>" class="blog-thumb" alt="">
      <?php else: ?>
      <div class="blog-thumb-ph"><i class="fa-solid fa-file-pen"></i></div>
      <?php endif; ?>
      <div class="blog-details">
        <div class="blog-title-row">
          <span class="blog-title"><?= htmlspecialchars($bl["title"]) ?></span>
          <span class="status-badge <?= $isPub ? "sb-pub" : "sb-draft" ?>">
            <i class="fa-solid fa-<?= $isPub ? "circle-check" : "clock" ?>"></i>
            <?= $isPub ? "Published" : "Draft" ?>
          </span>
        </div>
        <div class="blog-meta">
          <img loading="lazy" src="<?= htmlspecialchars($authorAv) ?>" class="author-av" alt="">
          <span style="color:var(--text);font-weight:800;"><?= htmlspecialchars($bl["author_name"] ?? "Admin") ?></span>
          <span>&middot;</span>
          <span><i class="fa-solid fa-heart" style="color:var(--pink)"></i> <?= (int)$bl["likes_count"] ?></span>
          <span>&middot;</span>
          <span><?= date("d M Y", strtotime($bl["created_at"])) ?></span>
          <?php foreach(array_slice($tagsArr,0,2) as $t): ?>
          <span class="blog-tag"><?= htmlspecialchars($t) ?></span>
          <?php endforeach; ?>
          <a href="blog.php?slug=<?= urlencode($bl["slug"]) ?>" target="_blank" class="view-lnk">View <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </div>
      <div class="action-btns">
        <a href="blog_edit.php?id=<?= $bl["id"] ?>" class="act-btn act-edit" title="Edit"><i class="fa-solid fa-pencil"></i></a>
        <form action="blog_toggle.php" method="POST" style="margin:0;">
          <input type="hidden" name="blog_id" value="<?= $bl["id"] ?>">
          <input type="hidden" name="status" value="<?= $isPub ? 0 : 1 ?>">
          <button type="submit" class="act-btn <?= $isPub ? "act-unpub" : "act-pub" ?>" title="<?= $isPub ? "Unpublish" : "Publish" ?>">
            <i class="fa-solid fa-<?= $isPub ? "eye-slash" : "rocket" ?>"></i>
          </button>
        </form>
        <button class="act-btn act-del" onclick="confirmDel(<?= $bl["id"] ?>, '<?= addslashes(htmlspecialchars($bl["title"])) ?>')" title="Delete">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>

    <!-- ── MOBILE CARD ── -->
    <div class="mob-blog-card <?= $isPub ? "" : "is-draft" ?>"
         data-title="<?= strtolower(htmlspecialchars($bl["title"])) ?>"
         data-tags="<?= strtolower(htmlspecialchars($bl["tags"] ?? "")) ?>">
      <div class="mob-card-top">
        <?php if($bl["image_path"]): ?>
        <img loading="lazy" src="<?= htmlspecialchars($bl["image_path"]) ?>" class="mob-thumb" alt="">
        <?php else: ?>
        <div class="mob-thumb-ph"><i class="fa-solid fa-file-pen"></i></div>
        <?php endif; ?>
        <div class="mob-card-info">
          <div class="mob-title"><?= htmlspecialchars($bl["title"]) ?></div>
          <div class="mob-badges">
            <span class="status-badge <?= $isPub ? "sb-pub" : "sb-draft" ?>">
              <i class="fa-solid fa-<?= $isPub ? "circle-check" : "clock" ?>"></i>
              <?= $isPub ? "Published" : "Draft" ?>
            </span>
            <?php foreach(array_slice($tagsArr,0,1) as $t): ?>
            <span class="blog-tag"><?= htmlspecialchars($t) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="mob-meta">
            <i class="fa-solid fa-heart" style="color:var(--pink)"></i> <?= (int)$bl["likes_count"] ?>
            &middot; <?= date("d M Y", strtotime($bl["created_at"])) ?>
          </div>
        </div>
        <!-- Three-dot button -->
        <div class="mob-more-btn" onclick="toggleDropdown(this)">
          <i class="fa-solid fa-ellipsis-vertical"></i>
          <div class="mob-dropdown">
            <a href="blog_edit.php?id=<?= $bl["id"] ?>" class="mob-dd-item dd-edit">
              <i class="fa-solid fa-pencil"></i> Edit Post
            </a>
            <form action="blog_toggle.php" method="POST" style="margin:0;">
              <input type="hidden" name="blog_id" value="<?= $bl["id"] ?>">
              <input type="hidden" name="status" value="<?= $isPub ? 0 : 1 ?>">
              <button type="submit" class="mob-dd-item <?= $isPub ? "dd-unpub" : "dd-pub" ?>" style="width:100%;background:none;border:none;font-family:var(--font);text-align:left;border-bottom:1px solid var(--border2);">
                <i class="fa-solid fa-<?= $isPub ? "eye-slash" : "rocket" ?>"></i>
                <?= $isPub ? "Unpublish" : "Publish" ?>
              </button>
            </form>
            <a href="blog.php?slug=<?= urlencode($bl["slug"]) ?>" target="_blank" class="mob-dd-item dd-view">
              <i class="fa-solid fa-eye"></i> View Post
            </a>
            <div class="mob-dd-item dd-del" onclick="confirmDel(<?= $bl["id"] ?>, '<?= addslashes(htmlspecialchars($bl["title"])) ?>')">
              <i class="fa-solid fa-trash"></i> Delete
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div><!-- end .main -->

<!-- ══ MOBILE BOTTOM NAV ══ -->
<nav class="mob-bottom-nav">
  <div class="mob-nav-items">
    <a href="dashboard.php" class="mob-nav-item"><i class="fa-solid fa-gauge-high"></i><span>Dash</span></a>
    <a href="manage_prompts.php" class="mob-nav-item"><i class="fa-solid fa-list-check"></i><span>Prompts</span></a>
    <a href="blog_admin.php" class="mob-nav-item active"><i class="fa-solid fa-pen-nib"></i><span>Blogs</span></a>
    <a href="user_management.php" class="mob-nav-item"><i class="fa-solid fa-users"></i><span>Users</span></a>
    <a href="analytics.php" class="mob-nav-item"><i class="fa-solid fa-chart-line"></i><span>Stats</span></a>
  </div>
</nav>

<!-- DELETE MODAL -->
<div id="del-modal" class="modal-ov">
  <div class="modal-box">
    <div class="del-icon"><i class="fa-solid fa-trash"></i></div>
    <h3 style="font-size:1.2rem;font-weight:900;margin-bottom:8px;">Delete Blog Post?</h3>
    <p id="del-modal-name" style="color:var(--muted);font-size:.88rem;font-weight:600;"></p>
    <div class="del-btns">
      <button class="del-cancel" onclick="document.getElementById('del-modal').style.display='none'">Cancel</button>
      <form id="del-form" action="blog_delete.php" method="POST" style="flex:1;margin:0;">
        <input type="hidden" id="del-blog-id" name="blog_id" value="">
        <button type="submit" class="del-confirm">Delete</button>
      </form>
    </div>
  </div>
</div>

<script>
/* ── Scroll progress ── */
window.addEventListener('scroll',()=>{
  const s=document.documentElement,b=document.body;
  const pct=(s.scrollTop||b.scrollTop)/((s.scrollHeight||b.scrollHeight)-s.clientHeight)*100;
  document.getElementById('sp').style.width=pct+'%';
});

/* ── Particle canvas ── */
(function(){
  const c=document.getElementById('pc'),ctx=c.getContext('2d');
  let W,H,pts=[];
  function resize(){W=c.width=innerWidth;H=c.height=innerHeight;}
  resize();window.addEventListener('resize',resize);
  for(let i=0;i<60;i++)pts.push({x:Math.random()*1920,y:Math.random()*1080,vx:(Math.random()-.5)*.25,vy:(Math.random()-.5)*.25,r:Math.random()*1.4+.4});
  function draw(){
    ctx.clearRect(0,0,W,H);
    pts.forEach(p=>{
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0)p.x=W;if(p.x>W)p.x=0;
      if(p.y<0)p.y=H;if(p.y>H)p.y=0;
      ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle='rgba(139,92,246,0.45)';ctx.fill();
    });
    requestAnimationFrame(draw);
  }
  draw();
})();

/* ── Custom cursor (desktop only) ── */
if(window.innerWidth > 768){
  const dot=document.getElementById('c-dot'),ring=document.getElementById('c-ring');
  let mx=0,my=0,rx=0,ry=0;
  document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;dot.style.left=mx+'px';dot.style.top=my+'px';});
  (function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(loop);})();
  document.querySelectorAll('a,button,input,.act-btn,.mob-more-btn').forEach(el=>{
    el.addEventListener('mouseenter',()=>document.body.classList.add('c-hover'));
    el.addEventListener('mouseleave',()=>document.body.classList.remove('c-hover'));
  });
  document.addEventListener('mousedown',()=>document.body.classList.add('c-click'));
  document.addEventListener('mouseup',()=>document.body.classList.remove('c-click'));
}

/* ── Mobile drawer ── */
function openDrawer(){
  document.getElementById('sideDrawer').classList.add('open');
  document.getElementById('drawerOverlay').classList.add('open');
}
function closeDrawer(){
  document.getElementById('sideDrawer').classList.remove('open');
  document.getElementById('drawerOverlay').classList.remove('open');
}

/* ── Three-dot dropdown ── */
function toggleDropdown(btn){
  const dd=btn.querySelector('.mob-dropdown');
  const isOpen=dd.classList.contains('open');
  // Close all other dropdowns
  document.querySelectorAll('.mob-dropdown.open').forEach(d=>d.classList.remove('open'));
  if(!isOpen) dd.classList.add('open');
}
// Close dropdown on outside click
document.addEventListener('click',e=>{
  if(!e.target.closest('.mob-more-btn')){
    document.querySelectorAll('.mob-dropdown.open').forEach(d=>d.classList.remove('open'));
  }
});

/* ── Search filter ── */
document.getElementById('blog-search')?.addEventListener('input', function(){
  const q=this.value.toLowerCase();
  document.querySelectorAll('[data-title]').forEach(item=>{
    const t=(item.dataset.title||'')+' '+(item.dataset.tags||'');
    item.style.display=t.includes(q)?'':'none';
  });
});

/* ── Delete modal ── */
function confirmDel(id,name){
  document.getElementById('del-blog-id').value=id;
  document.getElementById('del-modal-name').textContent='"'+name+'"';
  document.getElementById('del-modal').style.display='flex';
}
document.getElementById('del-modal')?.addEventListener('click',function(e){
  if(e.target===this)this.style.display='none';
});
</script>
</body>
</html>
