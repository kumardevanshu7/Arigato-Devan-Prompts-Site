<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: blog_admin.php"); exit(); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title=$_POST['title']??''; $description=$_POST['description']??'';
    $content=$_POST['content']??''; $meta_title=$_POST['meta_title']??'';
    $meta_desc=$_POST['meta_description']??''; $tags=$_POST['tags']??'';
    $image_ratio=$_POST['image_ratio']??'16:9';
    $publish=isset($_POST['publish'])?1:0;
    if(!$title){$_SESSION['edit_error']="Title required.";header("Location: blog_edit.php?id=$id");exit();}
    $image_path=$_POST['current_image'];
    if(isset($_FILES['image'])&&$_FILES['image']['error']===UPLOAD_ERR_OK){
        $ext=pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION);
        $fn='uploads/blog_'.uniqid().'.'.$ext;
        if(move_uploaded_file($_FILES['image']['tmp_name'],$fn)) $image_path=$fn;
    }
    $pdo->prepare("UPDATE blogs SET title=?,description=?,content=?,image_path=?,image_ratio=?,meta_title=?,meta_description=?,tags=?,is_published=?,updated_at=NOW() WHERE id=?")
        ->execute([$title,$description,$content,$image_path,$image_ratio,$meta_title,$meta_desc,$tags,$publish,$id]);
    $_SESSION['success_msg']="<i class='fa-solid fa-check'></i> Blog updated!"; header("Location: blog_admin.php"); exit();
}
$stmt=$pdo->prepare("SELECT * FROM blogs WHERE id=?"); $stmt->execute([$id]);
$bl=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$bl){header("Location: blog_admin.php");exit();}
$edit_error=$_SESSION['edit_error']??''; unset($_SESSION['edit_error']);
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Blog "â€ Admin</title><link rel="stylesheet" href="style.css?v=1777999999">
<style>
body{background:var(--bg-color)}.bc-wrap{max-width:900px;margin:0 auto;padding:36px 28px 100px}
.bc-title{font-size:2rem;font-weight:900;margin-bottom:6px}.bc-sub{color:#7D7887;font-weight:600;margin-bottom:28px}
.bc-card{background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:36px;box-shadow:var(--shadow-comic);margin-bottom:24px}
.bc-card h2{font-size:1.3rem;font-weight:900;margin-bottom:22px;padding-bottom:12px;border-bottom:2px dashed var(--border-color)}
.fg{margin-bottom:18px}.fg label{display:block;font-weight:800;margin-bottom:7px;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px}
.fg input,.fg textarea{width:100%;padding:11px 15px;border:var(--border-width) solid var(--text-color);border-radius:12px;font-family:var(--font-main);font-size:.95rem;font-weight:600;background:var(--bg-color);color:var(--text-color);box-shadow:var(--shadow-comic);outline:none;transition:all .2s;box-sizing:border-box}
.fg input:focus,.fg textarea:focus{border-color:var(--primary-dark);box-shadow:var(--shadow-comic-hover);transform:translateY(-1px)}
.fg textarea{resize:vertical;min-height:90px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.editor-toolbar{display:flex;flex-wrap:wrap;gap:6px;padding:10px 12px;background:var(--bg-color);border:var(--border-width) solid var(--text-color);border-bottom:none;border-radius:12px 12px 0 0}
.editor-toolbar button{padding:5px 11px;background:var(--card-bg);border:1.5px solid var(--border-color);border-radius:8px;font-family:var(--font-main);font-weight:800;font-size:.8rem;cursor:pointer;transition:all .15s}
.editor-toolbar button:hover{background:var(--primary-color);border-color:var(--text-color)}
.editor-toolbar .sep{width:1px;background:var(--border-color);margin:0 4px}
.editor-area{min-height:320px;padding:18px;border:var(--border-width) solid var(--text-color);border-radius:0 0 12px 12px;background:var(--bg-color);font-family:'Outfit','Inter',system-ui,sans-serif;font-size:1.05rem;line-height:1.8;outline:none;box-shadow:var(--shadow-comic);overflow-y:auto;color:#333;}
.editor-area:focus{border-color:var(--primary-dark)}
.editor-area h1,.editor-area h2,.editor-area h3{font-weight:800;color:var(--text-color);}
.editor-area p{margin-bottom:1rem;}
.editor-area img{max-width:100%;height:auto;border-radius:8px;cursor:pointer;border:2px solid transparent;}
.editor-area img:hover{border-color:var(--primary-dark);}
.font-style-btns{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.font-style-btns button{padding:5px 12px;border:1.5px solid var(--border-color);border-radius:20px;cursor:pointer;font-size:.82rem;font-weight:700;background:var(--card-bg);transition:all .15s}
.font-style-btns button:hover{border-color:var(--text-color);background:var(--primary-color)}
.img-preview{display:flex;align-items:center;gap:14px;padding:12px;background:var(--bg-color);border:2px dashed var(--border-color);border-radius:12px;margin-bottom:10px}
.img-preview img{width:80px;height:50px;object-fit:cover;border-radius:8px;border:2px solid var(--text-color)}
.file-upload-wrapper{display:flex;align-items:center;gap:14px;background:var(--bg-color);padding:10px 15px;border:var(--border-width) solid var(--text-color);border-radius:12px;box-shadow:var(--shadow-comic)}
.file-upload-btn{background:var(--primary-color);color:var(--text-color);padding:7px 14px;border:2px solid var(--text-color);border-radius:8px;font-weight:800;cursor:pointer;white-space:nowrap;font-size:.88rem;box-shadow:2px 2px 0 var(--text-color)}
.file-upload-name{font-weight:600;color:#7D7887;font-size:.88rem}
.pub-toggle{display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--secondary-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer}
.pub-toggle input[type=checkbox]{width:20px;height:20px;cursor:pointer;accent-color:var(--primary-dark)}
.btn-row{display:flex;gap:14px;margin-top:4px}
.btn-cancel{display:inline-flex;align-items:center;justify-content:center;padding:14px 22px;background:var(--bg-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;text-decoration:none;box-shadow:var(--shadow-comic);transition:all .2s;flex:1;text-align:center}
.btn-cancel:hover{transform:translateY(-2px);box-shadow:var(--shadow-comic-hover)}
.flash-error{background:#ffe6e6;color:#a70000;padding:14px;border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;margin-bottom:18px;box-shadow:3px 3px 0 var(--text-color)}
@media(max-width:640px){.form-row{grid-template-columns:1fr}.bc-card{padding:22px 18px}}
</style><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head><body>
<header>
  <div class="logo-area" onclick="window.location.href='index.php'" style="cursor:pointer">
    <div class="logo-flipper"><div class="logo-front"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo" id="profile-logo"></div><div class="logo-back"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt=""></div></div>
    <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
  </div>
  <nav class="nav-links"><a href="index.php">HOME</a><a href="dashboard.php">DASHBOARD</a><a href="blog_admin.php" style="background:var(--primary-color);border:2px solid var(--text-color);box-shadow:3px 3px 0 var(--text-color);border-radius:20px;"><i class="fa-solid fa-pencil"></i> BLOGS</a></nav>
  <div class="header-right"><div class="header-divider"></div>
    <div style="display:flex; align-items:center; gap:8px;"><a href="profile.php" title="Edit Profile"><?= renderAvatar($_SESSION['profile_image'] ?? '', 'admin-avatar', 'Admin', 'style="transition: transform 0.2s;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"') ?></a><a href="dashboard.php" style="color:var(--text-color); font-weight:800;">ADMIN</a></div>
    <a href="login.php?logout=1" class="logout"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> LOGOUT</a>
  </div>
</header>
<div class="bc-wrap">
  <div class="bc-title">Ã¢Å“ÂÃ¯Â¸Â Edit Blog</div>
  <div class="bc-sub">Editing: <strong><?=htmlspecialchars($bl['title'])?></strong></div>
  <?php if($edit_error):?><div class="flash-error"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($edit_error)?></div><?php endif;?>
  <form method="POST" action="blog_edit.php?id=<?=$id?>" enctype="multipart/form-data">
    <input type="hidden" name="current_image" value="<?=htmlspecialchars($bl['image_path'])?>">
    <div class="bc-card"><h2><i class="fa-solid fa-align-left"></i> Content</h2>
      <div class="fg"><label>Title *</label><input type="text" name="title" value="<?=htmlspecialchars($bl['title'])?>" required></div>
      <div class="fg"><label>Short Description</label><textarea name="description" rows="3"><?=htmlspecialchars($bl['description']??'')?></textarea></div>
      <div class="fg"><label>Content *</label>
        <div class="editor-toolbar">
          <button type="button" onclick="fmt('bold')"><b>B</b></button><button type="button" onclick="fmt('italic')"><i>I</i></button><button type="button" onclick="fmt('underline')"><u>U</u></button>
          <div class="sep"></div>
          <button type="button" onclick="fmtBlock('h1')">H1</button><button type="button" onclick="fmtBlock('h2')">H2</button><button type="button" onclick="fmtBlock('h3')">H3</button><button type="button" onclick="fmtBlock('p')"><i class="fa-solid fa-paragraph"></i></button>
          <div class="sep"></div>
          <button type="button" onclick="fmt('justifyLeft')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg></button>
          <button type="button" onclick="fmt('justifyCenter')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="10" x2="6" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="18" y1="18" x2="6" y2="18"></line></svg></button>
          <button type="button" onclick="fmt('justifyRight')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="7" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="21" y1="18" x2="7" y2="18"></line></svg></button>
          <div class="sep"></div>
          <button type="button" onclick="fmt('insertUnorderedList')"><i class="fa-solid fa-list-ul"></i> List</button><button type="button" onclick="fmt('insertOrderedList')"><i class="fa-solid fa-list-ol"></i> List</button>
          <div class="sep"></div>
          <button type="button" onclick="fmtBlock('blockquote')"><i class="fa-solid fa-quote-left"></i> Quote</button>
          <button type="button" onclick="document.getElementById('editor-img-upload').click()"><i class="fa-solid fa-image"></i> Image</button>
          <input type="file" id="editor-img-upload" style="display:none" accept="image/*" onchange="if(this.files[0]) uploadEditorImage(this.files[0])">
        </div>
        <div class="editor-area" id="blog-editor" contenteditable="true"><?= $bl['content'] ?></div>
        <div style="margin-top:10px;"><span style="font-size:.8rem;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.5px;">Font Styles:</span>
        <div class="font-style-btns">
          <button type="button" onclick="applyFontStyle('font-serif')" style="font-family:Georgia,serif">Serif</button>
          <button type="button" onclick="applyFontStyle('font-mono')" style="font-family:monospace">Mono</button>
          <button type="button" onclick="applyFontStyle('font-bold')" style="font-weight:900">Bold</button>
          <button type="button" onclick="applyFontStyle('font-light')" style="color:#888">Light</button>
          <button type="button" onclick="applyFontStyle('font-highlight')" style="background:#FFF1B8;border-radius:4px;padding:3px 8px;">Highlight</button>
        </div></div>
        <input type="hidden" name="content" id="blog-content-input">
      </div>
    </div>
    <div class="bc-card"><h2><i class="fa-solid fa-image"></i> Cover Image</h2>
      <?php if($bl['image_path']): ?><div class="img-preview"><img src="<?=htmlspecialchars($bl['image_path'])?>" alt=""><span>Current image</span></div><?php endif; ?>
      <div class="file-upload-wrapper" style="margin-bottom:18px"><label for="e-img" class="file-upload-btn">Replace Image</label><span class="file-upload-name" id="e-fname">No file chosen</span><input type="file" id="e-img" name="image" accept="image/*" style="display:none" onchange="document.getElementById('e-fname').textContent=this.files[0]?.name||'No file chosen'"></div>
      <div class="fg">
        <label>Aspect Ratio</label>
        <select name="image_ratio">
          <option value="16:9" <?=($bl['image_ratio']??'')==='16:9'?'selected':''?>>Landscape (16:9)</option>
          <option value="9:16" <?=($bl['image_ratio']??'')==='9:16'?'selected':''?>>Portrait (9:16)</option>
        </select>
      </div>
    </div>
    <div class="bc-card"><h2><i class="fa-solid fa-magnifying-glass"></i> SEO</h2>
      <div class="fg"><label>Meta Title</label><input type="text" name="meta_title" value="<?=htmlspecialchars($bl['meta_title']??'')?>"></div>
      <div class="fg"><label>Meta Description</label><textarea name="meta_description" rows="2"><?=htmlspecialchars($bl['meta_description']??'')?></textarea></div>
      <div class="fg"><label>Tags / Keywords</label><input type="text" name="tags" value="<?=htmlspecialchars($bl['tags']??'')?>"></div>
    </div>
    <div class="bc-card"><h2><i class="fa-solid fa-rocket"></i> Publish</h2>
      <div class="fg"><label class="pub-toggle" for="e-pub"><input type="checkbox" id="e-pub" name="publish" value="1" <?=$bl['is_published']?'checked':''?>> Published (uncheck to set as draft)</label></div>
      <div class="btn-row"><a href="blog_admin.php" class="btn-cancel"><i class="fa-solid fa-arrow-left"></i> Cancel</a><button type="submit" class="comic-btn" style="flex:2;background:var(--secondary-color)">Save Changes <i class="fa-solid fa-check"></i></button></div>
    </div>
  </form>
</div>
<script>
const editor=document.getElementById('blog-editor'); const input=document.getElementById('blog-content-input');
document.querySelector('form').addEventListener('submit',()=>{input.value=editor.innerHTML;});
function fmt(cmd){document.execCommand(cmd,false,null);editor.focus();}
function fmtBlock(tag){document.execCommand('formatBlock',false,'<'+tag+'>');editor.focus();}
function applyFontStyle(cls){const sel=window.getSelection();if(!sel.rangeCount)return;const range=sel.getRangeAt(0);if(range.collapsed)return;const span=document.createElement('span');span.className=cls;try{range.surroundContents(span);}catch(e){}editor.focus();}

function insertImagePrompt() {
    const url = prompt("Enter image URL:");
    if (url) {
        document.execCommand('insertImage', false, url);
        editor.focus();
    }
}

editor.addEventListener('dragover', (e) => {
    e.preventDefault();
    editor.style.borderColor = 'var(--primary-dark)';
});
editor.addEventListener('dragleave', () => {
    editor.style.borderColor = 'var(--text-color)';
});
editor.addEventListener('drop', (e) => {
    e.preventDefault();
    editor.style.borderColor = 'var(--text-color)';
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        for (let i = 0; i < files.length; i++) {
            if (files[i].type.startsWith('image/')) {
                uploadEditorImage(files[i]);
            }
        }
    }
});

function uploadEditorImage(file) {
    const formData = new FormData();
    formData.append('file', file);
    fetch('upload_editor_image.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.url) {
            editor.focus();
            document.execCommand('insertImage', false, data.url);
        } else { alert('Upload failed'); }
    }).catch(err => alert('Upload error'));
}
</script>
</body></html>











