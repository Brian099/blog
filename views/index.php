<?php require VIEW_PATH . '/layouts/header.php'; ?>

<main class="main-wrapper">
    <!-- Left Sidebar: Year-Grouped Article Index Tree -->
    <aside id="article-sidebar" class="article-sidebar">
        <div class="sidebar-header">
            <h2 class="sidebar-title">
                <?php if ($activeCategory): ?>
                    分类：<?= htmlspecialchars($activeCategory['cate_Name']) ?>
                <?php elseif ($activeTag): ?>
                    标签：#<?= htmlspecialchars($activeTag['tag_Name']) ?>
                <?php else: ?>
                    全部文章
                <?php endif; ?>
            </h2>
            <span class="sidebar-count"><?= $totalArticles ?> 篇</span>
        </div>

        <!-- Category switch chips inside sidebar for mobile -->
        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: -6px;">
            <a href="/" class="tag-badge <?= empty($_GET['cate']) && empty($_GET['tag']) ? 'category-badge' : '' ?>" style="font-size: 0.75rem;">全部</a>
            <?php foreach ($categories as $cat): ?>
                <a href="/?cate=<?= $cat['cate_ID'] ?>" class="tag-badge <?= (isset($_GET['cate']) && (int)$_GET['cate'] === (int)$cat['cate_ID']) ? 'category-badge' : '' ?>" style="font-size: 0.75rem;">
                    <?= htmlspecialchars($cat['cate_Name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-search-box">
            <svg class="sidebar-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="sidebar-quick-search" class="sidebar-search-input" placeholder="在列表中即时过滤...">
        </div>

        <div class="sidebar-scroll-area">
            <?php if (empty($tree)): ?>
                <div style="padding: 20px 0; text-align: center; color: var(--text-light); font-size: 0.85rem;">
                    暂无文章
                </div>
            <?php else: ?>
                <?php foreach ($tree as $year => $posts): ?>
                    <div class="year-group">
                        <div class="year-title">
                            <span><?= $year ?></span>
                            <span class="year-count"><?= count($posts) ?> 篇</span>
                        </div>
                        <ul class="post-tree-list">
                            <?php foreach ($posts as $p): ?>
                                <?php $isActive = ($currentPost && (int)$currentPost['id'] === (int)$p['id']); ?>
                                <li class="post-tree-item <?= $isActive ? 'active' : '' ?>">
                                    <a href="/?id=<?= $p['id'] ?><?= !empty($_GET['cate']) ? '&cate='.(int)$_GET['cate'] : '' ?><?= !empty($_GET['tag']) ? '&tag='.(int)$_GET['tag'] : '' ?>" 
                                       class="post-nav-link" 
                                       data-id="<?= $p['id'] ?>"
                                       title="<?= htmlspecialchars($p['title']) ?>">
                                        <?php if (!empty($p['is_top'])): ?>
                                            <span class="top-badge">置顶</span>
                                        <?php endif; ?>
                                        <?php if (!empty($p['is_protected'])): ?>
                                            <span title="密码保护文章" style="font-size: 0.8rem; margin-right: 2px;">🔒</span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($p['title']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Right Main Area: Immersive Article Reading Area -->
    <section class="article-main">
        <div id="article-content-wrapper" style="width: 100%; display: flex; justify-content: center; transition: opacity 0.15s ease;">
            <?php $post = $currentPost; require VIEW_PATH . '/partials/post-content.php'; ?>
        </div>
    </section>
</main>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
