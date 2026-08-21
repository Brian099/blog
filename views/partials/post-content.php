<?php
/** @var array $post */
?>
<?php if ($post): ?>
    <article class="article-container">
        <header class="article-header">
            <div class="article-tags-header">
                <?php if (!empty($post['category'])): ?>
                    <a href="/?cate=<?= $post['category']['cate_ID'] ?>" class="category-badge">
                        <?= htmlspecialchars($post['category']['cate_Name']) ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($post['tags'])): ?>
                    <span>标签：</span>
                    <?php foreach ($post['tags'] as $tag): ?>
                        <a href="/?tag=<?= $tag['tag_ID'] ?>" class="tag-badge">
                            #<?= htmlspecialchars($tag['tag_Name']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h1 class="article-title"><?= htmlspecialchars($post['title']) ?></h1>

            <div class="article-meta">
                <span><?= $post['date_formatted'] ?></span>
                <span>·</span>
                <span>阅读需 <?= $post['read_time'] ?> 分钟</span>
                <span>·</span>
                <span><?= number_format($post['views']) ?> 次阅读</span>
            </div>
        </header>

        <div class="article-body">
            <?= $post['content'] ?>
        </div>

        <?php if (!empty($post['prev']) || !empty($post['next'])): ?>
            <nav class="article-footer-nav">
                <?php if (!empty($post['prev'])): ?>
                    <a href="/?id=<?= $post['prev']['log_ID'] ?>" class="nav-card post-nav-link" data-id="<?= $post['prev']['log_ID'] ?>">
                        <span class="nav-label">← 上一篇</span>
                        <span class="nav-title"><?= htmlspecialchars($post['prev']['log_Title']) ?></span>
                    </a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <?php if (!empty($post['next'])): ?>
                    <a href="/?id=<?= $post['next']['log_ID'] ?>" class="nav-card post-nav-link" data-id="<?= $post['next']['log_ID'] ?>" style="text-align: right;">
                        <span class="nav-label">下一篇 →</span>
                        <span class="nav-title"><?= htmlspecialchars($post['next']['log_Title']) ?></span>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </article>
<?php else: ?>
    <div style="text-align: center; padding: 100px 20px; color: var(--text-light);">
        <h2>暂无文章</h2>
        <p style="margin-top: 10px;">请在左侧列表中选择一篇文章查看。</p>
    </div>
<?php endif; ?>
