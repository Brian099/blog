/**
 * Minimalist Tech Blog - Dual-Column Interactive Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initArticleNavigation();
    initCodeHighlightAndCopy();
    initImageLightbox();
    initInstantSearch();
    initSidebarFilter();
    initMobileDrawer();
});

/* ==========================================================================
   Theme Switcher (Dark / Light)
   ========================================================================== */
function initTheme() {
    const themeBtn = document.getElementById('theme-toggle-btn');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const savedTheme = localStorage.getItem('theme') || (prefersDark ? 'dark' : 'light');

    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });
    }
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('theme-icon');
    if (!icon) return;
    if (theme === 'dark') {
        icon.innerHTML = '<path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>';
    } else {
        icon.innerHTML = '<path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" fill="currentColor"/>';
    }
}

/* ==========================================================================
   Article Navigation (Left Sidebar Ajax Fetch without Refresh)
   ========================================================================== */
function initArticleNavigation() {
    const articleContainer = document.getElementById('article-content-wrapper');

    document.addEventListener('click', async (e) => {
        const link = e.target.closest('.post-nav-link');
        if (!link) return;

        e.preventDefault();
        const postId = link.getAttribute('data-id');
        if (!postId) return;

        // 更新高亮激活状态
        document.querySelectorAll('.post-tree-item').forEach(el => el.classList.remove('active'));
        const parentItem = link.closest('.post-tree-item');
        if (parentItem) parentItem.classList.add('active');

        // 加载文章
        await loadPost(postId, link.href);

        // 如果是移动端抽屉模式，点击后自动收起
        const sidebar = document.getElementById('article-sidebar');
        if (sidebar && sidebar.classList.contains('drawer-open')) {
            sidebar.classList.remove('drawer-open');
        }
    });

    // 监听浏览器前进/后退
    window.addEventListener('popstate', (e) => {
        const params = new URLSearchParams(window.location.search);
        const postId = params.get('id');
        if (postId) {
            loadPost(postId, window.location.href, false);
            // 同步左侧激活项
            document.querySelectorAll('.post-tree-item').forEach(el => el.classList.remove('active'));
            const activeLink = document.querySelector(`.post-nav-link[data-id="${postId}"]`);
            if (activeLink) activeLink.closest('.post-tree-item').classList.add('active');
        }
    });
}

async function loadPost(postId, url, pushState = true) {
    const container = document.getElementById('article-content-wrapper');
    if (!container) return;

    // 显示轻微加载过渡
    container.style.opacity = '0.5';

    try {
        const res = await fetch(`/api/post?id=${postId}`);
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();

        container.innerHTML = data.html;
        document.title = `${data.title} - ${window.SITE_NAME || '博客'}`;

        if (pushState) {
            history.pushState({ postId }, '', url);
        }

        // 重新初始化代码高亮与灯箱
        initCodeHighlightAndCopy();
        initImageLightbox();

        // 平滑滚动到文章顶部
        const mainArea = document.querySelector('.article-main');
        if (mainArea) mainArea.scrollTop = 0;

    } catch (err) {
        console.error('Failed to load post:', err);
    } finally {
        container.style.opacity = '1';
    }
}

/* ==========================================================================
   Code Highlighting & Copy Button
   ========================================================================== */
function initCodeHighlightAndCopy() {
    // 触发 Highlight.js (如果已加载)
    if (window.hljs) {
        document.querySelectorAll('pre code').forEach((block) => {
            window.hljs.highlightElement(block);
        });
    }

    // 绑定一键复制代码
    document.querySelectorAll('.copy-code-btn').forEach((btn) => {
        btn.onclick = async (e) => {
            e.stopPropagation();
            const wrapper = btn.closest('.code-block-wrapper');
            const codeEl = wrapper ? wrapper.querySelector('code') : null;
            if (!codeEl) return;

            const text = codeEl.innerText;
            try {
                await navigator.clipboard.writeText(text);
                btn.innerText = '已复制 ✓';
                btn.style.color = '#22c55e';
                setTimeout(() => {
                    btn.innerText = '复制';
                    btn.style.color = '';
                }, 2000);
            } catch (err) {
                btn.innerText = '复制失败';
            }
        };
    });
}

/* ==========================================================================
   Password Protected Post Unlock
   ========================================================================== */
window.submitPostUnlock = async function(e, postId) {
    if (e) e.preventDefault();
    const pwdInput = document.getElementById('post-unlock-pwd');
    const errorMsg = document.getElementById('unlock-error-msg');
    const submitBtn = document.getElementById('unlock-submit-btn');

    if (!pwdInput || !pwdInput.value) return;

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerText = '正在验证解锁...';
    }
    if (errorMsg) errorMsg.style.display = 'none';

    try {
        const form = new FormData();
        form.append('id', postId);
        form.append('password', pwdInput.value.trim());

        const res = await fetch('/post/unlock', { method: 'POST', body: form });
        const data = await res.json();

        if (data.success) {
            const container = document.getElementById('article-content-wrapper') || document.getElementById('article-content-container');
            if (container) {
                container.innerHTML = data.html;
                if (data.title) document.title = data.title;
                // 重新初始化代码高亮与灯箱
                initCodeBlocks();
                initLightbox();
            }
        } else {
            if (errorMsg) {
                errorMsg.innerText = data.message || '密码错误';
                errorMsg.style.display = 'block';
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerText = '立即解锁文章 🔓';
            }
            pwdInput.focus();
            pwdInput.select();
        }
    } catch (err) {
        if (errorMsg) {
            errorMsg.innerText = '网络连接失败，请稍后再试';
            errorMsg.style.display = 'block';
        }
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerText = '立即解锁文章 🔓';
        }
    }
};

/* ==========================================================================
   Image Lightbox (Click to View Full Size)
   ========================================================================== */
function initImageLightbox() {
    const modal = document.getElementById('lightbox-modal');
    const modalImg = document.getElementById('lightbox-img');
    if (!modal || !modalImg) return;

    document.querySelectorAll('.article-body img').forEach((img) => {
        img.style.cursor = 'zoom-in';
        img.onclick = () => {
            modalImg.src = img.src;
            modal.classList.add('open');
        };
    });

    modal.onclick = () => {
        modal.classList.remove('open');
    };
}

/* ==========================================================================
   Instant Search Modal
   ========================================================================== */
function initInstantSearch() {
    const modal = document.getElementById('search-modal');
    const searchBtn = document.getElementById('search-toggle-btn');
    const searchInput = document.getElementById('global-search-input');
    const resultsContainer = document.getElementById('search-results-list');

    if (!modal || !searchBtn) return;

    // 打开搜索弹窗
    const openSearch = () => {
        modal.classList.add('open');
        setTimeout(() => searchInput && searchInput.focus(), 50);
    };
    const closeSearch = () => {
        modal.classList.remove('open');
    };

    searchBtn.onclick = openSearch;

    // 快捷键 '/' 或 'Cmd/Ctrl + K'
    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            openSearch();
        }
        if (e.key === 'Escape' && modal.classList.contains('open')) {
            closeSearch();
        }
    });

    modal.onclick = (e) => {
        if (e.target === modal) closeSearch();
    };

    // 实时搜索防抖
    let timer = null;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            const val = searchInput.value.trim();
            if (!val) {
                resultsContainer.innerHTML = '<li style="padding: 16px; text-align: center; color: var(--text-light);">输入关键字搜索 620+ 篇技术文章</li>';
                return;
            }

            timer = setTimeout(async () => {
                resultsContainer.innerHTML = '<li style="padding: 16px; text-align: center; color: var(--text-light);">搜索中...</li>';
                try {
                    const res = await fetch(`/api/search?q=${encodeURIComponent(val)}`);
                    const data = await res.json();
                    if (!data.results || data.results.length === 0) {
                        resultsContainer.innerHTML = '<li style="padding: 16px; text-align: center; color: var(--text-light);">未找到相关文章</li>';
                        return;
                    }

                    resultsContainer.innerHTML = data.results.map(item => `
                        <li class="search-result-item">
                            <a href="/?id=${item.id}" class="post-nav-link" data-id="${item.id}" onclick="document.getElementById('search-modal').classList.remove('open')">
                                <h4>${item.title_highlight}</h4>
                                <p>${item.snippet_highlight}</p>
                            </a>
                        </li>
                    `).join('');
                } catch (e) {
                    resultsContainer.innerHTML = '<li style="padding: 16px; text-align: center; color: #ef4444;">搜索失败</li>';
                }
            }, 250);
        });
    }
}

/* ==========================================================================
   Left Sidebar Filter
   ========================================================================== */
function initSidebarFilter() {
    const input = document.getElementById('sidebar-quick-search');
    if (!input) return;

    input.addEventListener('input', () => {
        const val = input.value.trim().toLowerCase();
        const items = document.querySelectorAll('.post-tree-item');
        const yearGroups = document.querySelectorAll('.year-group');

        items.forEach(item => {
            const title = item.querySelector('a').innerText.toLowerCase();
            if (!val || title.includes(val)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });

        // 隐藏没有任何匹配项的年份组
        yearGroups.forEach(group => {
            const visibleItems = group.querySelectorAll('.post-tree-item:not([style*="display: none"])');
            group.style.display = visibleItems.length > 0 ? 'block' : 'none';
        });
    });
}

/* ==========================================================================
   Mobile Sidebar Drawer
   ========================================================================== */
function initMobileDrawer() {
    const toggleBtn = document.getElementById('sidebar-toggle-btn');
    const sidebar = document.getElementById('article-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    const openDrawer = () => {
        if (sidebar) sidebar.classList.add('drawer-open');
        if (overlay) overlay.classList.add('active');
    };

    const closeDrawer = () => {
        if (sidebar) sidebar.classList.remove('drawer-open');
        if (overlay) overlay.classList.remove('active');
    };

    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebar && sidebar.classList.contains('drawer-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeDrawer);
    }
}
