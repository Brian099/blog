<?php
/** @var array $post */
$siteSettings = $siteSettings ?? \App\Models\Setting::getAll();
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
                <?php if (!empty($siteSettings['author_name'])): ?>
                    <span style="font-weight: 500;">✍️ <?= htmlspecialchars($siteSettings['author_name']) ?></span>
                    <span>·</span>
                <?php endif; ?>
                <span><?= $post['date_formatted'] ?></span>
                <span>·</span>
                <span>阅读需 <?= $post['read_time'] ?> 分钟</span>
                <span>·</span>
                <span><?= number_format($post['views']) ?> 次阅读</span>
            </div>
        </header>

        <!-- Article Body -->
        <div class="article-body">
            <?php if ($post['is_protected'] && !$post['is_unlocked']): ?>
                <!-- Password Protection Lock Card -->
                <div class="password-lock-card" style="margin: 40px auto; max-width: 480px; padding: 36px 28px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); text-align: center; box-shadow: var(--shadow-md);">
                    <div style="width: 56px; height: 56px; margin: 0 auto 18px; border-radius: 50%; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>

                    <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-title); margin-bottom: 8px;">这是一篇密码受保护的文章</h3>
                    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 24px; line-height: 1.5;">作者为本篇技术记录设置了访问密码，请输入正确密码以解锁完整正文。</p>

                    <?php if (!empty($post['intro'])): ?>
                        <div style="text-align: left; padding: 12px 16px; background: var(--bg-hover); border-left: 3px solid var(--primary); border-radius: var(--radius-sm); font-size: 0.88rem; color: var(--text-main); margin-bottom: 20px;">
                            <strong>文章摘要：</strong><?= htmlspecialchars($post['intro']) ?>
                        </div>
                    <?php endif; ?>

                    <form id="post-unlock-form" data-post-id="<?= $post['id'] ?>" onsubmit="submitPostUnlock(event, <?= $post['id'] ?>)" style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="position: relative;">
                            <input type="password" id="post-unlock-pwd" class="form-input" placeholder="输入访问密码..." required 
                                   style="width: 100%; padding: 12px 16px; font-size: 0.95rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--bg-main); color: var(--text-main); text-align: center; letter-spacing: 2px;">
                        </div>
                        <div id="unlock-error-msg" style="display: none; color: #dc2626; font-size: 0.84rem; font-weight: 500;"></div>
                        <button type="submit" id="unlock-submit-btn" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 0.95rem; font-weight: 600; justify-content: center;">
                            立即解锁文章 🔓
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <?= $post['content'] ?>
            <?php endif; ?>
        </div>

        <!-- Author Bio Card -->
        <?php if (!empty($siteSettings['author_name']) || !empty($siteSettings['author_bio'])): ?>
            <div class="article-author-card" style="margin-top: 40px; margin-bottom: 24px; padding: 18px 22px; background: var(--bg-hover); border-radius: var(--radius-md); border: 1px solid var(--border-color); display: flex; gap: 16px; align-items: center;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.15rem; flex-shrink: 0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <?= mb_substr($siteSettings['author_name'] ?? 'B', 0, 1, 'UTF-8') ?>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap;">
                        <span style="font-size: 0.95rem; font-weight: 700; color: var(--text-title);"><?= htmlspecialchars($siteSettings['author_name'] ?? '作者') ?></span>
                        <?php if (!empty($siteSettings['site_subtitle'])): ?>
                            <span style="font-size: 0.8rem; color: var(--text-light);"><?= htmlspecialchars($siteSettings['site_subtitle']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($siteSettings['author_bio'])): ?>
                        <p style="font-size: 0.84rem; color: var(--text-muted); margin-top: 3px; line-height: 1.5;"><?= htmlspecialchars($siteSettings['author_bio']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

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
        
        <!-- Semantic Article Site Footer -->
        <footer class="article-site-footer">
            <div class="footer-meta">
                <span>© <?= date('Y') ?> <?= htmlspecialchars($siteSettings['site_name'] ?? '技术思维棱镜') ?></span>
                <span>·</span>
                <span>Powered by Modern Z-Blog</span>
                <?php if (!empty($siteSettings['site_icp'])): ?>
                    <span>·</span>
                    <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($siteSettings['site_icp']) ?></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($siteSettings['site_subtitle'])): ?>
                <div class="footer-quote">
                    <?= htmlspecialchars($siteSettings['site_subtitle']) ?>
                </div>
            <?php endif; ?>
        </footer>
    </article>
<?php else: ?>
    <div style="text-align: center; padding: 100px 20px; color: var(--text-light);">
        <h2>暂无文章</h2>
        <p style="margin-top: 10px;">请在左侧列表中选择一篇文章查看。</p>
    </div>
<?php endif; ?>
