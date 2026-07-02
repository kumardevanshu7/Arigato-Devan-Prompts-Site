(function () {
    'use strict';

    var MIN_SKELETON_MS = 550;

    try {
        if (new URLSearchParams(window.location.search).get('skeletondebug') === '1') {
            MIN_SKELETON_MS = 2000;
        }
    } catch (e) { /* ignore */ }

    function revealLoaded(img, startedAt) {
        var elapsed = Date.now() - startedAt;
        var delay = Math.max(0, MIN_SKELETON_MS - elapsed);

        setTimeout(function () {
            img.classList.add('loaded');
            var card = img.closest('.skeleton');
            if (card) card.classList.remove('skeleton');
        }, delay);
    }

    function bindSkeletonImg(img) {
        if (img.dataset.skeletonBound === '1') return;
        img.dataset.skeletonBound = '1';

        var startedAt = Date.now();

        function onReady() {
            revealLoaded(img, startedAt);
        }

        if (img.complete && img.naturalWidth > 0) {
            onReady();
            return;
        }

        img.addEventListener('load', onReady, { once: true });
        img.addEventListener('error', onReady, { once: true });
    }

    function scan(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('.skeleton-img').forEach(bindSkeletonImg);
        scope.querySelectorAll('.skeleton img:not(.skeleton-img)').forEach(function (img) {
            img.classList.add('skeleton-img');
            bindSkeletonImg(img);
        });
    }

    window.initCardSkeletons = scan;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { scan(document); });
    } else {
        scan(document);
    }

    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) scan(node);
                });
            });
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
