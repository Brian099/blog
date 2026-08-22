<!-- Search Modal -->
<div id="search-modal" class="search-modal-backdrop">
    <div class="search-dialog">
        <div class="search-input-wrapper">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--text-light);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="global-search-input" placeholder="输入文章标题或关键字搜索..." autocomplete="off">
            <span style="font-size: 0.8rem; color: var(--text-light);"><kbd>ESC</kbd> 退出</span>
        </div>
        <ul id="search-results-list" class="search-results-list">
            <li style="padding: 24px; text-align: center; color: var(--text-light);">输入关键字即刻在 620+ 篇技术记录中搜索</li>
        </ul>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div id="lightbox-modal" class="lightbox-modal">
    <img id="lightbox-img" src="" alt="Zoomed image">
</div>

<script src="/assets/js/app.js?v=<?= file_exists(APP_PATH . '/../public/assets/js/app.js') ? filemtime(APP_PATH . '/../public/assets/js/app.js') : time() ?>"></script>
</body>
</html>
