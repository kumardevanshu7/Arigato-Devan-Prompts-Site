<?php
/**
 * SEO & Authority Content Section for Homepage (index.php)
 * High-value semantic SEO, humanized original copy, comparison table, category cards & workflow guide.
 * Tailored for Gemini Nano Banana 2, Nano Banana Pro & ChatGPT Image 2.
 */
?>
<section class="ad-seo-master" id="about-prompts" aria-label="About Arigato Devan AI Prompts">
    <style>
    /* ============================================================
       ARIGATO DEVAN SEO MASTER SECTION STYLES
       ============================================================ */
    .ad-seo-master {
        width: 100%;
        max-width: 1180px;
        margin: 60px auto 40px;
        padding: 0 16px;
        box-sizing: border-box;
        color: var(--text-primary, #2F4156);
        font-family: var(--font-body, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
    }

    /* Hero & Introduction */
    .ad-seo-hero {
        text-align: center;
        max-width: 900px;
        margin: 0 auto 48px;
    }

    .ad-seo-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, rgba(86, 124, 141, 0.12), rgba(200, 217, 230, 0.35));
        border: 1px solid rgba(86, 124, 141, 0.28);
        padding: 7px 20px;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--pal-teal, #567C8D);
        margin-bottom: 18px;
    }

    .ad-seo-pill i {
        color: #ff6b6b;
    }

    .ad-seo-hero h2 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(1.85rem, 4.2vw, 2.85rem);
        font-weight: 800;
        line-height: 1.24;
        color: var(--text-primary, #2F4156);
        margin: 0 0 18px;
        letter-spacing: -0.02em;
    }

    .ad-seo-hero h2 span.gradient-text {
        background: linear-gradient(135deg, #2F4156 0%, #567C8D 60%, #ff6b6b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .ad-seo-lead {
        font-size: clamp(0.98rem, 2vw, 1.15rem);
        line-height: 1.8;
        color: var(--text-secondary, #567C8D);
        margin: 0 auto 18px;
        text-align: center;
    }

    .ad-seo-lead strong {
        color: var(--text-primary, #2F4156);
        font-weight: 700;
    }

    .ad-author-badge {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        background: rgba(245, 239, 235, 0.7);
        border: 1px solid rgba(47, 65, 86, 0.12);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.88rem;
        color: var(--text-primary, #2F4156);
        margin-top: 10px;
    }

    .ad-author-badge img {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Problem vs Solution Comparison Duo */
    .ad-compare-duo {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 26px;
        margin-bottom: 56px;
    }

    .ad-compare-card {
        background: var(--bg-card, #ffffff);
        border-radius: 24px;
        padding: 32px 28px;
        box-shadow: 0 8px 24px rgba(47, 65, 86, 0.05);
        border: 1.5px solid rgba(47, 65, 86, 0.08);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        position: relative;
        overflow: hidden;
    }

    .ad-compare-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 32px rgba(47, 65, 86, 0.1);
    }

    .ad-compare-card.problem {
        border-color: rgba(239, 68, 68, 0.25);
        background: linear-gradient(180deg, rgba(254, 242, 242, 0.65) 0%, var(--bg-card, #ffffff) 100%);
    }

    .ad-compare-card.solution {
        border-color: rgba(16, 185, 129, 0.28);
        background: linear-gradient(180deg, rgba(236, 253, 245, 0.75) 0%, var(--bg-card, #ffffff) 100%);
    }

    .ad-card-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 14px;
    }

    .ad-compare-card.problem .ad-card-badge { color: #dc2626; }
    .ad-compare-card.solution .ad-card-badge { color: #059669; }

    .ad-compare-card h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 1.45rem;
        font-weight: 800;
        margin: 0 0 12px;
        color: var(--text-primary, #2F4156);
        line-height: 1.3;
    }

    .ad-compare-card p.card-intro {
        font-size: 0.95rem;
        line-height: 1.7;
        color: var(--text-secondary, #567C8D);
        margin-bottom: 22px;
    }

    /* Fixed Check List Styling — Prevents Text Squeezing */
    .ad-check-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .ad-check-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 16px;
    }

    .ad-check-item:last-child {
        margin-bottom: 0;
    }

    .ad-check-bullet {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.88rem;
        margin-top: 3px;
    }

    .ad-compare-card.problem .ad-check-bullet {
        background: rgba(239, 68, 68, 0.15);
        color: #dc2626;
    }

    .ad-compare-card.solution .ad-check-bullet {
        background: rgba(16, 185, 129, 0.15);
        color: #059669;
    }

    .ad-check-body {
        flex: 1;
        font-size: 0.94rem;
        line-height: 1.7;
        color: var(--text-secondary, #567C8D);
    }

    .ad-check-body strong {
        color: var(--text-primary, #2F4156);
        font-weight: 700;
        display: inline;
        margin-right: 4px;
    }

    /* Engine Comparison Matrix Table */
    .ad-matrix-section {
        background: var(--bg-card, #ffffff);
        border: 1.5px solid rgba(47, 65, 86, 0.1);
        border-radius: 24px;
        padding: 38px 32px;
        margin-bottom: 56px;
        box-shadow: 0 10px 30px rgba(47, 65, 86, 0.05);
    }

    .ad-matrix-head {
        text-align: center;
        max-width: 760px;
        margin: 0 auto 30px;
    }

    .ad-matrix-head h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(1.6rem, 3.5vw, 2.2rem);
        font-weight: 800;
        margin: 0 0 10px;
        color: var(--text-primary, #2F4156);
    }

    .ad-matrix-head p {
        font-size: 0.96rem;
        color: var(--text-secondary, #567C8D);
        line-height: 1.65;
        margin: 0;
    }

    .ad-table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 16px;
        border: 1px solid rgba(47, 65, 86, 0.12);
        box-shadow: 0 4px 14px rgba(47, 65, 86, 0.03);
    }

    .ad-matrix-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        min-width: 680px;
        background: #ffffff;
    }

    .ad-matrix-table th {
        background: linear-gradient(135deg, #2F4156 0%, #3e5973 100%);
        color: #ffffff;
        padding: 18px 22px;
        font-size: 0.92rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .ad-matrix-table td {
        padding: 18px 22px;
        font-size: 0.92rem;
        line-height: 1.6;
        border-bottom: 1px solid rgba(47, 65, 86, 0.08);
        color: var(--text-primary, #2F4156);
        vertical-align: middle;
    }

    .ad-matrix-table tr:last-child td {
        border-bottom: none;
    }

    .ad-matrix-table tr:hover td {
        background: rgba(200, 217, 230, 0.15);
    }

    .ad-engine-name {
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-primary, #2F4156);
        font-size: 0.98rem;
    }

    .ad-engine-name i {
        color: #567C8D;
        font-size: 1.15rem;
    }

    .ad-tag-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.76rem;
        font-weight: 700;
        background: rgba(86, 124, 141, 0.12);
        color: var(--pal-teal, #567C8D);
    }

    /* Category Deep Dive (2x2 Grid) */
    .ad-cat-section {
        margin-bottom: 56px;
    }

    .ad-cat-section-header {
        text-align: center;
        max-width: 820px;
        margin: 0 auto 36px;
    }

    .ad-cat-section-header h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(1.7rem, 3.8vw, 2.4rem);
        font-weight: 800;
        margin: 0 0 12px;
        color: var(--text-primary, #2F4156);
    }

    .ad-cat-section-header p {
        font-size: 0.98rem;
        color: var(--text-secondary, #567C8D);
        line-height: 1.7;
        margin: 0;
    }

    .ad-cat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
    }

    .ad-cat-box {
        background: var(--bg-card, #ffffff);
        border: 1.5px solid rgba(47, 65, 86, 0.1);
        border-radius: 22px;
        padding: 30px 26px;
        box-shadow: 0 6px 22px rgba(47, 65, 86, 0.04);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
    }

    .ad-cat-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(47, 65, 86, 0.1);
        border-color: var(--pal-teal, #567C8D);
    }

    .ad-cat-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(86, 124, 141, 0.12), rgba(200, 217, 230, 0.4));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: var(--pal-navy, #2F4156);
        margin-bottom: 20px;
    }

    .ad-cat-box h4 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 1.3rem;
        font-weight: 800;
        margin: 0 0 12px;
        color: var(--text-primary, #2F4156);
        line-height: 1.3;
    }

    .ad-cat-box p {
        font-size: 0.92rem;
        line-height: 1.72;
        color: var(--text-secondary, #567C8D);
        margin: 0 0 20px;
        flex-grow: 1;
    }

    .ad-cat-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: auto;
    }

    .ad-cat-tag {
        font-size: 0.74rem;
        font-weight: 700;
        padding: 5px 11px;
        border-radius: 999px;
        background: rgba(47, 65, 86, 0.06);
        color: var(--text-primary, #2F4156);
    }

    /* 4-Step Guide (Horizontal Grid) */
    .ad-steps-section {
        background: linear-gradient(135deg, rgba(245, 239, 235, 0.8) 0%, rgba(200, 217, 230, 0.3) 100%);
        border: 1.5px solid rgba(47, 65, 86, 0.12);
        border-radius: 24px;
        padding: 42px 30px;
        margin-bottom: 56px;
    }

    .ad-steps-header {
        text-align: center;
        max-width: 740px;
        margin: 0 auto 36px;
    }

    .ad-steps-header h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(1.65rem, 3.6vw, 2.3rem);
        font-weight: 800;
        margin: 0 0 10px;
        color: var(--text-primary, #2F4156);
    }

    .ad-steps-header p {
        font-size: 0.96rem;
        color: var(--text-secondary, #567C8D);
        margin: 0;
        line-height: 1.6;
    }

    .ad-steps-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
    }

    .ad-step-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 26px 22px;
        border: 1px solid rgba(47, 65, 86, 0.1);
        box-shadow: 0 4px 14px rgba(47, 65, 86, 0.04);
        position: relative;
    }

    .ad-step-num {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--pal-teal, #567C8D);
        margin-bottom: 12px;
        display: inline-block;
        opacity: 0.9;
    }

    .ad-step-card h4 {
        font-size: 1.1rem;
        font-weight: 800;
        margin: 0 0 10px;
        color: var(--text-primary, #2F4156);
    }

    .ad-step-card p {
        font-size: 0.9rem;
        line-height: 1.65;
        color: var(--text-secondary, #567C8D);
        margin: 0;
    }

    /* Photography Parameters Cheat Sheet */
    .ad-optics-section {
        background: var(--bg-card, #ffffff);
        border: 1.5px solid rgba(47, 65, 86, 0.1);
        border-radius: 24px;
        padding: 38px 32px;
        margin-bottom: 56px;
        box-shadow: 0 8px 26px rgba(47, 65, 86, 0.04);
    }

    .ad-optics-head {
        text-align: center;
        max-width: 780px;
        margin: 0 auto 30px;
    }

    .ad-optics-head h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(1.6rem, 3.5vw, 2.2rem);
        font-weight: 800;
        margin: 0 0 10px;
        color: var(--text-primary, #2F4156);
    }

    .ad-optics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 18px;
    }

    .ad-optics-box {
        background: rgba(245, 239, 235, 0.4);
        border: 1px solid rgba(47, 65, 86, 0.08);
        border-radius: 16px;
        padding: 22px 20px;
    }

    .ad-optics-box h5 {
        font-size: 1rem;
        font-weight: 800;
        color: var(--text-primary, #2F4156);
        margin: 0 0 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ad-optics-box h5 i {
        color: #ff6b6b;
    }

    .ad-optics-box p {
        font-size: 0.88rem;
        line-height: 1.65;
        color: var(--text-secondary, #567C8D);
        margin: 0;
    }

    /* FAQ Section */
    .ad-faq-wrap {
        background: var(--bg-card, #ffffff);
        border: 1.5px solid rgba(47, 65, 86, 0.1);
        border-radius: 24px;
        padding: 42px 32px;
        margin-bottom: 46px;
        box-shadow: 0 6px 24px rgba(47, 65, 86, 0.04);
    }

    .ad-faq-wrap h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(1.65rem, 3.5vw, 2.2rem);
        font-weight: 800;
        text-align: center;
        margin: 0 0 32px;
        color: var(--text-primary, #2F4156);
    }

    .ad-faq-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 22px;
    }

    .ad-faq-item {
        background: rgba(245, 239, 235, 0.4);
        border: 1px solid rgba(47, 65, 86, 0.08);
        border-radius: 18px;
        padding: 22px;
    }

    .ad-faq-item h4 {
        font-size: 1.02rem;
        font-weight: 800;
        color: var(--text-primary, #2F4156);
        margin: 0 0 10px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        line-height: 1.4;
    }

    .ad-faq-item h4 i {
        color: #ff6b6b;
        font-size: 1rem;
        margin-top: 3px;
        flex-shrink: 0;
    }

    .ad-faq-item p {
        font-size: 0.9rem;
        line-height: 1.7;
        color: var(--text-secondary, #567C8D);
        margin: 0;
        padding-left: 24px;
    }

    /* High-Intent Keyword Cloud Pills */
    .ad-seo-cloud {
        margin-top: 42px;
        padding-top: 30px;
        border-top: 1px dashed rgba(47, 65, 86, 0.16);
        text-align: center;
    }

    .ad-seo-cloud p.cloud-title {
        font-size: 0.88rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-secondary, #567C8D);
        margin-bottom: 16px;
    }

    .ad-pills-row {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        justify-content: center;
    }

    .ad-pill-link {
        font-size: 0.74rem;
        font-weight: 700;
        padding: 7px 15px;
        border-radius: 999px;
        background: var(--pal-sky, #C8D9E6);
        color: var(--text-primary, #2F4156);
        text-decoration: none;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .ad-pill-link:hover {
        background: var(--pal-teal, #567C8D);
        color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(86, 124, 141, 0.28);
    }

    @media (max-width: 768px) {
        .ad-seo-master { padding: 0 10px; margin-top: 40px; }
        .ad-matrix-section, .ad-steps-section, .ad-optics-section, .ad-faq-wrap { padding: 26px 16px; border-radius: 20px; }
        .ad-compare-card { padding: 24px 18px; }
        .ad-compare-duo, .ad-cat-grid, .ad-steps-row, .ad-optics-grid, .ad-faq-grid { grid-template-columns: 1fr; }
        .ad-faq-item p { padding-left: 0; margin-top: 8px; }
    }
    </style>

    <!-- 1. Hero Introduction & Hook -->
    <div class="ad-seo-hero">
        <div class="ad-seo-pill">
            <i class="fa-solid fa-wand-magic-sparkles"></i> The Independent AI Photo Prompt Studio
        </div>
        <h2>
            <span class="gradient-text">Gemini Nano Banana 2 &amp; ChatGPT Image 2</span> Couple Prompts That Actually Keep Your Real Face
        </h2>
        <p class="ad-seo-lead">
            Have you ever spotted a breathtaking <strong>AI couple photo</strong> on Instagram Reels, copied the prompt into ChatGPT or Gemini, uploaded pictures of you and your partner, and received an output featuring two completely unrecognizable strangers? You are not alone. Generating believable, emotionally touching couple portraits with <strong>consistent facial identities</strong> is the single most demanding challenge in modern AI image generation.
        </p>
        <p class="ad-seo-lead" style="font-size:0.96rem;margin-bottom:14px;">
            The problem is almost never your source photographs or the AI application itself — <strong>the breakdown happens in the prompt structure</strong>. Generic prompts simply ask for a "romantic couple", which gives the neural diffusion model complete freedom to invent arbitrary facial features, smooth away your skin textures, and distort human anatomy.
        </p>
        <div class="ad-author-badge">
            <i class="fa-solid fa-circle-check" style="color:#059669;"></i>
            <span>Curated, structured &amp; multi-run verified by <strong>Kumar Devanshu</strong> &bull; Updated for 2026 Models</span>
        </div>
    </div>

    <!-- 2. Problem vs Solution Detailed Analysis -->
    <div class="ad-compare-duo">
        <div class="ad-compare-card problem">
            <div class="ad-card-badge"><i class="fa-solid fa-triangle-exclamation"></i> The Common Prompt Flaw</div>
            <h3>Why 90% of Online Prompts Fail on Couples</h3>
            <p class="card-intro">
                Most prompt lists across blogs and social media are auto-scraped keyword dumps that fail to instruct the underlying vision model on facial anatomy, dual-identity isolation, and spatial perspective.
            </p>
            <ul class="ad-check-list">
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-xmark"></i></span>
                    <div class="ad-check-body">
                        <strong>Identity Drift &amp; Facial Amalgamation:</strong> Without strict identity constraints, the AI blends the boy's jawline with the girl's facial structure, resulting in two synthetic, generic strangers.
                    </div>
                </li>
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-xmark"></i></span>
                    <div class="ad-check-body">
                        <strong>Plastic Airbrushed Skin:</strong> Vague prompts trigger aggressive digital smoothing that erases authentic pores, real skin tones, natural wrinkles, and believable cheek contours.
                    </div>
                </li>
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-xmark"></i></span>
                    <div class="ad-check-body">
                        <strong>Anatomical Deformities:</strong> Close hugs and hand-holding often produce extra fingers, twisted wrists, floating arms, and awkwardly proportioned heads.
                    </div>
                </li>
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-xmark"></i></span>
                    <div class="ad-check-body">
                        <strong>Mismatched Environmental Lighting:</strong> The AI often renders one partner under golden-hour daylight while casting flat studio shadows across the other.
                    </div>
                </li>
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-xmark"></i></span>
                    <div class="ad-check-body">
                        <strong>Fabric &amp; Cultural Misinterpretation:</strong> Indian wedding wear (sarees, lehengas, sherwanis) gets rendered as blurry, muddy costumes instead of authentic textiles.
                    </div>
                </li>
            </ul>
        </div>

        <div class="ad-compare-card solution">
            <div class="ad-card-badge"><i class="fa-solid fa-circle-check"></i> The Arigato Devan Standard</div>
            <h3>Engineered for High-Fidelity Photorealism</h3>
            <p class="card-intro">
                Every prompt on Arigato Devan is written like a professional film director's shot script — featuring explicit negative constraints, dual-subject facial anchors, and optical camera physics.
            </p>
            <ul class="ad-check-list">
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-check"></i></span>
                    <div class="ad-check-body">
                        <strong>Strict Dual-Identity Lock:</strong> Explicit hierarchical instructions command Gemini and ChatGPT to treat your uploaded photographs as the absolute identity benchmark.
                    </div>
                </li>
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-check"></i></span>
                    <div class="ad-check-body">
                        <strong>DSLR 85mm Portrait Optics:</strong> Emulates full-frame DSLR camera physics with authentic shallow depth-of-field, creamy background bokeh, and lifelike eye catchlights.
                    </div>
                </li>
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-check"></i></span>
                    <div class="ad-check-body">
                        <strong>Micro-Texture Realism:</strong> Demands authentic skin pores, fine stray hair strands, natural skin blemishes, and physically correct textile weaving.
                    </div>
                </li>
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-check"></i></span>
                    <div class="ad-check-body">
                        <strong>Ergonomic Hand &amp; Joint Grounding:</strong> Poses are scripted with clear spatial landmarks (e.g. holding a smartphone mirror selfie, adjusting a jhumka earring) to avoid deformed fingers.
                    </div>
                </li>
                <li class="ad-check-item">
                    <span class="ad-check-bullet"><i class="fa-solid fa-check"></i></span>
                    <div class="ad-check-body">
                        <strong>Multi-Run Testing Protocol:</strong> Prompts are generated repeatedly across multiple runs. If either face drifts or anatomy breaks down, the prompt is reworked before publishing.
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <!-- 3. AI Engines Breakdown (Interactive Table) -->
    <div class="ad-matrix-section">
        <div class="ad-matrix-head">
            <div class="ad-seo-pill" style="margin-bottom:10px;"><i class="fa-solid fa-microchip"></i> Engine Compatibility</div>
            <h3>Supported AI Image Generation Models</h3>
            <p>
                We intentionally optimize our prompt library for the world's two premier generative vision ecosystems: <strong>Google Gemini (Nano Banana)</strong> and <strong>OpenAI ChatGPT Image 2</strong>. Here is how they compare for couple portraiture:
            </p>
        </div>

        <div class="ad-table-wrap">
            <table class="ad-matrix-table">
                <thead>
                    <tr>
                        <th>AI Model &amp; Ecosystem</th>
                        <th>Core Photographic Strengths</th>
                        <th>Face Fidelity &amp; Realism</th>
                        <th>Recommended Prompt Themes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="ad-engine-name">
                                <i class="fa-brands fa-google"></i> Gemini Nano Banana 2
                            </div>
                            <span class="ad-tag-badge" style="margin-top:4px;">Google DeepMind</span>
                        </td>
                        <td>Incredible natural Indian skin tone reproduction, soft ambient daylight falloff, and lightning-fast portrait rendering.</td>
                        <td><strong style="color:#059669;">Superior (9.6 / 10)</strong> &mdash; Superb retention of individual eyelid shapes, natural nose bridges, and authentic smiles.</td>
                        <td>Romantic couple selfies, candid outdoor park dates, sunset beach walks, and forehead kiss moments.</td>
                    </tr>
                    <tr>
                        <td>
                            <div class="ad-engine-name">
                                <i class="fa-solid fa-bolt"></i> Nano Banana Pro
                            </div>
                            <span class="ad-tag-badge" style="margin-top:4px;">High-Fidelity Engine</span>
                        </td>
                        <td>Ultra-high-density textile rendering (zari work, embroidery), 3-panel sequential collages, and rich 8K atmospheric depth.</td>
                        <td><strong style="color:#059669;">Near-Flawless (9.8 / 10)</strong> &mdash; Zero identity drift across multi-panel vertical triptychs and complex seated postures.</td>
                        <td>Traditional wedding lehengas, rainy road triptychs, cozy elevator mirror collages &amp; retro 80s film edits.</td>
                    </tr>
                    <tr>
                        <td>
                            <div class="ad-engine-name">
                                <i class="fa-solid fa-robot"></i> ChatGPT Image 2 (GPT-4o)
                            </div>
                            <span class="ad-tag-badge" style="margin-top:4px;">OpenAI Studio</span>
                        </td>
                        <td>Exceptional narrative mood storytelling, dramatic low-light candlelight falloff, and seamless in-scene typography.</td>
                        <td><strong style="color:#2563eb;">Excellent (9.3 / 10)</strong> &mdash; Premium editorial magazine aesthetic with rich optical depth of field and warm color palettes.</td>
                        <td>Candlelit restaurant dinners, moody cafe dates, viral boys attitude reels &amp; cinematic rooftop proposals.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p style="margin:14px auto 0;max-width:850px;font-size:0.78rem;color:var(--text-secondary,#567C8D);line-height:1.5;text-align:center;">
            <i class="fa-solid fa-circle-info" style="color:var(--pal-teal,#567C8D);margin-right:4px;"></i>
            <em>* Note: Ratings are editorial evaluations based on hands-on prompt testing across 50+ benchmark generations conducted by Arigato Devan Studio.</em>
        </p>
    </div>

    <!-- 4. Categories Deep Dive (4 Visual Modern Boxes) -->
    <div class="ad-cat-section">
        <div class="ad-cat-section-header">
            <div class="ad-seo-pill" style="margin-bottom:10px;"><i class="fa-solid fa-shapes"></i> Curated Collections</div>
            <h3>Explore AI Photo Prompts by Creative Vibe &amp; Style</h3>
            <p>Whether you're creating content for Instagram Reels, celebrating an anniversary, or producing festive portraits, our prompt library is divided into targeted aesthetic categories.</p>
        </div>

        <div class="ad-cat-grid">
            <!-- Box 1 -->
            <div class="ad-cat-box">
                <div class="ad-cat-icon"><i class="fa-solid fa-heart"></i></div>
                <h4>Romantic Couple Prompts</h4>
                <p>
                    Our signature specialty and the most requested category on the internet. Includes tender forehead kisses, sun-drenched beach strolls, cozy elevator mirror selfies, and rustic cafe dates. Every prompt specifies natural physical distance, believable arm placements, and warm ambient lighting to keep both lovers' faces genuine, emotionally expressive, and completely undistorted.
                </p>
                <div class="ad-cat-tags">
                    <span class="ad-cat-tag">couple prompt</span>
                    <span class="ad-cat-tag">gemini couple prompt</span>
                    <span class="ad-cat-tag">romantic mirror selfie</span>
                    <span class="ad-cat-tag">forehead kiss prompt</span>
                </div>
            </div>

            <!-- Box 2 -->
            <div class="ad-cat-box">
                <div class="ad-cat-icon"><i class="fa-solid fa-person-running"></i></div>
                <h4>Instagram Viral Boys Prompts</h4>
                <p>
                    Engineered specifically for high-engagement Instagram Reels and YouTube Shorts. Features moody attitude portraits, tailored quiet luxury fashion, superbike and sports car aesthetics, low-angle perspective photography, and cinematic rim lighting that highlights sharp jawlines, textured hair, and confident posture.
                </p>
                <div class="ad-cat-tags">
                    <span class="ad-cat-tag">boys viral prompt</span>
                    <span class="ad-cat-tag">reels aesthetic</span>
                    <span class="ad-cat-tag">quiet luxury boy</span>
                    <span class="ad-cat-tag">cinematic attitude</span>
                </div>
            </div>

            <!-- Box 3 -->
            <div class="ad-cat-box">
                <div class="ad-cat-icon"><i class="fa-solid fa-gem"></i></div>
                <h4>Aesthetic Girls &amp; Ethnic Prompts</h4>
                <p>
                    Celebrating both rich traditional Indian grandeur and contemporary western elegance. Prompts feature authentic saree drapes (silk, chiffon, pastel organza), intricate bridal lehengas with shimmering sequin details, delicate gold jhumka earrings, soft golden-hour radiance, and expressive, lifelike eyes without artificial beauty filters.
                </p>
                <div class="ad-cat-tags">
                    <span class="ad-cat-tag">saree photo prompt</span>
                    <span class="ad-cat-tag">lehenga bridal edit</span>
                    <span class="ad-cat-tag">golden hour girl</span>
                    <span class="ad-cat-tag">traditional jhumka</span>
                </div>
            </div>

            <!-- Box 4 -->
            <div class="ad-cat-box">
                <div class="ad-cat-icon"><i class="fa-solid fa-film"></i></div>
                <h4>3-Panel Storytelling &amp; Collages</h4>
                <p>
                    Vertical 4:5 and 9:16 triptych formats that narrate a cinematic mini-story in a single image. Panel 1 establishes the setting (e.g. playful mirror selfie), Panel 2 captures an intimate emotional glance, and Panel 3 captures spontaneous, candid laughter. Engineered with strict visual continuity so both people maintain identical outfits and hairstyles across all three frames.
                </p>
                <div class="ad-cat-tags">
                    <span class="ad-cat-tag">3 panel collage</span>
                    <span class="ad-cat-tag">photo triptych</span>
                    <span class="ad-cat-tag">elevator selfie</span>
                    <span class="ad-cat-tag">film grain aesthetic</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Optical Cheat Sheet Section -->
    <div class="ad-optics-section">
        <div class="ad-optics-head">
            <div class="ad-seo-pill" style="margin-bottom:10px;"><i class="fa-solid fa-camera"></i> Behind the Realism</div>
            <h3>The Prompt Engineering Secrets Behind Our 8K Output</h3>
            <p>We don't use buzzwords like "hyperrealistic 8K masterpiece". Instead, we embed genuine physical camera parameters into our prompts to guide the AI's rendering engine:</p>
        </div>

        <div class="ad-optics-grid">
            <div class="ad-optics-box">
                <h5><i class="fa-solid fa-circle-dot"></i> Focal Length Physics</h5>
                <p>Prompts explicitly specify <strong>85mm f/1.8 prime portrait lenses</strong> for intimate couple shots (flattering facial compression without nose distortion) or <strong>35mm f/2.8</strong> for environmental street dating scenes.</p>
            </div>

            <div class="ad-optics-box">
                <h5><i class="fa-solid fa-sun"></i> Volumetric Light Modeling</h5>
                <p>We dictate exact light sources: warm <strong>golden-hour backlight</strong>, directional Rembrandt side lighting, or practical ambient candlelight, ensuring both subjects share physically coherent highlights.</p>
            </div>

            <div class="ad-optics-box">
                <h5><i class="fa-solid fa-expand"></i> Aspect Ratio Optimization</h5>
                <p>Formatted in native social media aspect ratios — <strong>3:4</strong> for Instagram vertical feed portrait posts, and <strong>9:16</strong> for full-screen viral Reels and WhatsApp status updates.</p>
            </div>

            <div class="ad-optics-box">
                <h5><i class="fa-solid fa-shield-halved"></i> Negative Anatomical Barriers</h5>
                <p>Every prompt includes strict negative prompts that actively forbid facial morphing, unnatural beauty smoothing, extra fingers, cartoon shading, and identity cross-contamination.</p>
            </div>
        </div>
    </div>

    <!-- 6. How to Use in 4 Steps -->
    <div class="ad-steps-section">
        <div class="ad-steps-header">
            <div class="ad-seo-pill" style="margin-bottom:10px;"><i class="fa-solid fa-list-ol"></i> Step-by-Step Guide</div>
            <h3>How to Use Any Prompt in 4 Simple Steps</h3>
            <p>No technical coding or prompt engineering knowledge required. Follow this battle-tested process to create flawless AI images in minutes.</p>
        </div>

        <div class="ad-steps-row">
            <div class="ad-step-card">
                <span class="ad-step-num">01</span>
                <h4>Browse &amp; Pick a Look</h4>
                <p>Explore the Arigato Devan feed and pick a prompt that matches the vibe, outfits, and setting you want to recreate.</p>
            </div>

            <div class="ad-step-card">
                <span class="ad-step-num">02</span>
                <h4>1-Click Copy Prompt</h4>
                <p>Click the <strong>Copy Prompt</strong> button. If the prompt has a secret code, enter the 6-letter key from our Instagram reel or guide to instantly reveal it.</p>
            </div>

            <div class="ad-step-card">
                <span class="ad-step-num">03</span>
                <h4>Upload Photos &amp; Paste</h4>
                <p>Open <strong>Gemini Nano Banana 2</strong> or <strong>ChatGPT Image 2</strong>, upload clear, well-lit reference photos of you and your partner, and paste the prompt verbatim.</p>
            </div>

            <div class="ad-step-card">
                <span class="ad-step-num">04</span>
                <h4>Generate &amp; Go Viral</h4>
                <p>Hit generate! Within seconds, your photorealistic couple portrait is ready to post on Instagram Reels, WhatsApp status, or set as your couple wallpaper.</p>
            </div>
        </div>
    </div>

    <!-- 7. Frequently Asked Questions (FAQ) -->
    <div class="ad-faq-wrap">
        <h3>Frequently Asked Questions About AI Photo Prompts</h3>
        <div class="ad-faq-grid">
            <div class="ad-faq-item">
                <h4><i class="fa-solid fa-circle-question"></i> Are these AI couple prompts free to copy and use?</h4>
                <p>Yes, 100%! Every prompt in our library is free to copy. You can unlock prompts instantly as a guest, or log in with Google to unlock 4x faster (in just 20 taps) and save your favorite prompts permanently to your profile.</p>
            </div>

            <div class="ad-faq-item">
                <h4><i class="fa-solid fa-circle-question"></i> Which AI tool produces the best couple photos in 2026?</h4>
                <p>Both <strong>Gemini Nano Banana 2 / Nano Banana Pro</strong> and <strong>ChatGPT Image 2</strong> are industry leaders. Gemini excels at authentic South Asian skin tones, natural daylight, and fast generations, while ChatGPT Image 2 offers cinematic mood lighting and romantic editorial storytelling.</p>
            </div>

            <div class="ad-faq-item">
                <h4><i class="fa-solid fa-circle-question"></i> What kind of photo should I upload for best face accuracy?</h4>
                <p>Upload a clear, front-facing, well-lit photo where both individuals' facial features (eyes, nose, jawline) are unobstructed by dark sunglasses, hats, or heavy shadows. Avoid blurry, low-resolution selfies or heavily beauty-filtered photos for maximum identity preservation.</p>
            </div>

            <div class="ad-faq-item">
                <h4><i class="fa-solid fa-circle-question"></i> Why do Arigato Devan prompts prevent distorted hands and extra fingers?</h4>
                <p>We use ergonomic body language instructions (such as holding a smartphone at a realistic angle, hands resting naturally in pockets, or adjusting jewelry) along with negative anatomical clauses that constrain the AI's diffusion math, preventing unnatural hand deformities.</p>
            </div>

            <div class="ad-faq-item">
                <h4><i class="fa-solid fa-circle-question"></i> Can I suggest or request a custom prompt idea?</h4>
                <p>Absolutely! Kumar Devanshu regularly reviews user requests from our community. If you have an Instagram reel concept or festival aesthetic you'd love a prompt for, reach out through our contact page or Instagram DMs.</p>
            </div>

            <div class="ad-faq-item">
                <h4><i class="fa-solid fa-circle-question"></i> Can I use these generated images for commercial Instagram Reels?</h4>
                <p>Yes, images generated through your own Gemini or ChatGPT subscriptions using these prompts can be used across your social media channels, YouTube Shorts, and personal creative projects according to the AI provider's terms of service.</p>
            </div>
        </div>
    </div>

    <!-- 8. High-Intent Semantic Keyword Cloud -->
    <div class="ad-seo-cloud">
        <p class="cloud-title"><i class="fa-solid fa-magnifying-glass"></i> Popular Trending AI Prompt Searches</p>
        <div class="ad-pills-row">
            <a href="gallery.php?tag=romantic" class="ad-pill-link"><i class="fa-solid fa-heart"></i> couple prompt</a>
            <a href="gallery.php?tag=romantic" class="ad-pill-link"><i class="fa-brands fa-google"></i> couple prompt for gemini ai</a>
            <a href="gallery.php?tag=romantic" class="ad-pill-link"><i class="fa-solid fa-robot"></i> couple prompt chatgpt</a>
            <a href="gallery.php?tag=traditional" class="ad-pill-link"><i class="fa-solid fa-om"></i> couple prompt chatgpt indian</a>
            <a href="gallery.php?tag=romantic" class="ad-pill-link"><i class="fa-solid fa-fire"></i> trending couple prompt</a>
            <a href="gallery.php?tag=selfie" class="ad-pill-link"><i class="fa-solid fa-camera"></i> couple mirror selfie prompt</a>
            <a href="gallery.php?tag=traditional" class="ad-pill-link"><i class="fa-solid fa-gem"></i> traditional saree couple prompt</a>
            <a href="gallery.php?tag=candid" class="ad-pill-link"><i class="fa-solid fa-bolt"></i> gemini nano banana 2 prompt</a>
            <a href="gallery.php?tag=romantic" class="ad-pill-link"><i class="fa-solid fa-microchip"></i> nano banana pro couple prompts</a>
            <a href="gallery.php?tag=romantic" class="ad-pill-link"><i class="fa-solid fa-image"></i> chatgpt image 2 prompts</a>
            <a href="gallery.php?tag=kiss" class="ad-pill-link"><i class="fa-solid fa-kiss-wink-heart"></i> forehead kiss couple prompt</a>
            <a href="gallery.php?tag=collage" class="ad-pill-link"><i class="fa-solid fa-table-cells-large"></i> 3 panel couple collage prompt</a>
            <a href="gallery.php?tag=boys" class="ad-pill-link"><i class="fa-solid fa-person-running"></i> viral boys reel prompt</a>
            <a href="gallery.php?tag=girls" class="ad-pill-link"><i class="fa-solid fa-wand-magic-sparkles"></i> aesthetic girls photo prompt</a>
            <a href="gallery.php?tag=candid" class="ad-pill-link"><i class="fa-solid fa-face-smile"></i> candid couple photo prompt</a>
            <a href="gallery.php?tag=traditional" class="ad-pill-link"><i class="fa-solid fa-church"></i> indian wedding couple prompt</a>
            <a href="gallery.php?tag=night" class="ad-pill-link"><i class="fa-solid fa-moon"></i> night date couple prompt</a>
            <a href="gallery.php" class="ad-pill-link"><i class="fa-solid fa-arrow-right"></i> browse all 100+ prompts</a>
        </div>
    </div>
</section>
