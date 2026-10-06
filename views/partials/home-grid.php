<?php
/**
 * 首页卡片网格：标题区 + Hero & 2x2 推荐区 + 标签/分类区 + 双列文章卡片
 *
 * @var array|null $hero          顶部主推荐卡片
 * @var array      $features      2x2 推荐卡片
 * @var array      $latestPosts   双列最新发布卡片
 * @var array      $categories    全部分类
 * @var array      $tags          热门标签
 * @var array      $siteSettings  站点设置
 * @var array|null $activeCategory 当前选中分类
 * @var array|null $activeTag      当前选中标签
 * @var int        $totalArticles 当前筛选下的文章总数
 * @var bool       $topIsPinned   顶部区域是否来自置顶文章
 */

$svgClock = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
$svgEye   = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>';
?>
<div class="home-grid">

    <!-- 标题区 -->
    <header class="home-title-area">
        <h1 class="home-site-title"><?= htmlspecialchars($siteSettings['site_name'] ?? '技术思维棱镜') ?></h1>
        <?php if (!empty($siteSettings['site_subtitle'])): ?>
            <p class="home-site-subtitle"><?= htmlspecialchars($siteSettings['site_subtitle']) ?></p>
        <?php endif; ?>
        <div class="home-site-stats">
            <span><?= $totalArticles ?> 篇文章</span>
            <span class="dot">·</span>
            <span><?= count($categories) ?> 个分类</span>
            <?php if ($activeCategory || $activeTag): ?>
                <span class="dot">·</span>
                <span class="home-current-filter">
                    当前筛选：<?= $activeCategory ? htmlspecialchars($activeCategory['cate_Name']) : '#' . htmlspecialchars($activeTag['tag_Name']) ?>
                    <a href="/" class="home-filter-clear">清除</a>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <!-- 顶部推荐区：Hero + 2x2 -->
    <?php if ($hero): ?>
        <section class="home-top">
            <a class="hero-card" href="/?id=<?= (int)$hero['id'] ?>">
                <?php if (!empty($hero['is_top'])): ?>
                    <div class="home-card-head"><span class="card-pin">置顶</span></div>
                <?php elseif (empty($topIsPinned)): ?>
                    <div class="home-card-head"><span class="card-latest">最新</span></div>
                <?php endif; ?>

                <h2 class="hero-card-title">
                    <?php if (!empty($hero['is_protected'])): ?><span class="card-lock" title="密码保护">🔒</span><?php endif; ?>
                    <?= htmlspecialchars($hero['title']) ?>
                </h2>

                <?php if ($hero['excerpt'] !== ''): ?>
                    <p class="hero-card-excerpt"><?= htmlspecialchars($hero['excerpt']) ?></p>
                <?php endif; ?>

                <?php if (!empty($hero['tags'])): ?>
                    <div class="home-card-tags">
                        <?php foreach ($hero['tags'] as $tagName): ?>
                            <span class="card-tag">#<?= htmlspecialchars($tagName) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="home-card-meta">
                    <span class="card-stat"><?= $svgClock ?> 阅读需 <?= (int)$hero['read_time'] ?> 分钟</span>
                    <span class="card-stat"><?= $svgEye ?> <?= number_format((int)$hero['views']) ?> 次阅读</span>
                    <span class="card-stat"><?= htmlspecialchars($hero['date']) ?></span>
                </div>
            </a>

            <?php if (!empty($features)): ?>
                <div class="feature-grid">
                    <?php foreach ($features as $item): ?>
                        <a class="feature-card" href="/?id=<?= (int)$item['id'] ?>">
                            <?php if (!empty($item['is_top'])): ?>
                                <div class="home-card-head"><span class="card-pin">置顶</span></div>
                            <?php endif; ?>

                            <h3 class="feature-card-title">
                                <?php if (!empty($item['is_protected'])): ?><span class="card-lock" title="密码保护">🔒</span><?php endif; ?>
                                <?= htmlspecialchars($item['title']) ?>
                            </h3>

                            <?php if ($item['excerpt'] !== ''): ?>
                                <p class="feature-card-excerpt"><?= htmlspecialchars($item['excerpt']) ?></p>
                            <?php endif; ?>

                            <div class="home-card-meta">
                                <span class="card-stat"><?= $svgClock ?> <?= (int)$item['read_time'] ?> 分钟</span>
                                <span class="card-stat"><?= $svgEye ?> <?= number_format((int)$item['views']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- 标签 / 分类区 -->
    <?php if (!empty($categories) || !empty($tags)): ?>
        <section class="home-filters">
            <?php if (!empty($categories)): ?>
                <div class="filter-row">
                    <span class="filter-label">分类</span>
                    <div class="filter-chips">
                        <a href="/" class="filter-chip <?= (!$activeCategory && !$activeTag) ? 'active' : '' ?>">全部</a>
                        <?php foreach ($categories as $cat): ?>
                            <?php $isActive = $activeCategory && (int)$activeCategory['cate_ID'] === (int)$cat['cate_ID']; ?>
                            <a href="/?cate=<?= (int)$cat['cate_ID'] ?>" class="filter-chip <?= $isActive ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat['cate_Name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($tags)): ?>
                <div class="filter-row">
                    <span class="filter-label">标签</span>
                    <div class="filter-chips">
                        <?php foreach (array_slice($tags, 0, 14) as $tag): ?>
                            <?php $isActive = $activeTag && (int)$activeTag['tag_ID'] === (int)$tag['tag_ID']; ?>
                            <a href="/?tag=<?= (int)$tag['tag_ID'] ?>" class="filter-chip <?= $isActive ? 'active' : '' ?>">
                                #<?= htmlspecialchars($tag['tag_Name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- 双列最新发布 -->
    <section class="home-posts">
        <div class="home-section-head">
            <h2 class="home-section-title">最新发布</h2>
            <span class="home-section-count"><?= count($latestPosts) ?> 篇</span>
        </div>

        <?php if (empty($latestPosts)): ?>
            <div class="home-empty">暂无更多文章</div>
        <?php else: ?>
            <div class="post-grid">
                <?php foreach ($latestPosts as $item): ?>
                    <a class="post-card" href="/?id=<?= (int)$item['id'] ?>">
                        <div class="home-card-head">
                            <?php if (!empty($item['cate_name'])): ?>
                                <span class="card-cat"><?= htmlspecialchars($item['cate_name']) ?></span>
                            <?php endif; ?>
                            <span class="card-date"><?= htmlspecialchars($item['date']) ?></span>
                        </div>

                        <h3 class="post-card-title">
                            <?php if (!empty($item['is_protected'])): ?><span class="card-lock" title="密码保护">🔒</span><?php endif; ?>
                            <?= htmlspecialchars($item['title']) ?>
                        </h3>

                        <?php if ($item['excerpt'] !== ''): ?>
                            <p class="post-card-excerpt"><?= htmlspecialchars($item['excerpt']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($item['tags'])): ?>
                            <div class="home-card-tags">
                                <?php foreach ($item['tags'] as $tagName): ?>
                                    <span class="card-tag">#<?= htmlspecialchars($tagName) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="post-card-foot">
                            <span class="card-stat"><?= $svgClock ?> <?= (int)$item['read_time'] ?> 分钟</span>
                            <span class="card-stat"><?= $svgEye ?> <?= number_format((int)$item['views']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
