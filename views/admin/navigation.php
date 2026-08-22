<?php
$pageTitle = '导航与链接管理';
ob_start();

$customNavList = [];
if (!empty($settings['custom_nav'])) {
    $decoded = json_decode($settings['custom_nav'], true);
    if (is_array($decoded)) {
        $customNavList = $decoded;
    }
}
if (empty($customNavList)) {
    $customNavList[] = ['name' => '全部文章', 'url' => '/', 'target' => '_self'];
    if (!empty($categories)) {
        foreach ($categories as $c) {
            $customNavList[] = ['name' => $c['cate_Name'], 'url' => '/?cate=' . $c['cate_ID'], 'target' => '_self'];
        }
    }
}

$friendLinksList = [];
if (!empty($settings['friend_links'])) {
    $decoded = json_decode($settings['friend_links'], true);
    if (is_array($decoded)) {
        $friendLinksList = $decoded;
    }
}
?>

<form method="POST" action="/admin/navigation" id="navigation-form">
    <!-- 顶部标题栏与保存按钮 -->
    <div class="page-title-row">
        <div>
            <h2 class="page-title">导航与外链管理</h2>
            <p class="page-subtitle">自定义前台页面顶部导航栏按钮、分类直达入口及页脚全局友情链接</p>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; font-weight: 600; font-size: 0.92rem; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>保存导航与链接设置</span>
            </button>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div style="padding: 12px 18px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: var(--radius); margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <input type="hidden" name="custom_nav" id="custom_nav_input">
    <input type="hidden" name="friend_links" id="friend_links_input">

    <!-- 模块 1：前台顶栏导航按钮管理 -->
    <div class="card" style="margin-bottom: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #eff6ff; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                </div>
                <div>
                    <h3 class="card-title">顶栏导航按钮管理</h3>
                    <p class="card-desc">配置展示在网站头部左侧的导航链接，支持内部分类、自定义单页与外部跳转</p>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="addNavRow()" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.84rem;">
                    + 新增导航按钮
                </button>
                <button type="button" onclick="loadCategoriesToNav()" class="btn btn-outline" style="padding: 6px 14px; font-size: 0.84rem;">
                    ⚡ 导入全部分类
                </button>
                <button type="button" onclick="resetDefaultNav()" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.84rem; color: #ef4444;" title="恢复系统默认导航">
                    ↺ 恢复默认
                </button>
            </div>
        </div>

        <div style="overflow-x: auto; border: 1px solid var(--admin-border); border-radius: var(--radius);">
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">序号</th>
                        <th style="width: 220px;">按钮名称 <span style="color:#ef4444;">*</span></th>
                        <th>跳转链接 (URL / 相对路径) <span style="color:#ef4444;">*</span></th>
                        <th style="width: 140px;">打开方式</th>
                        <th style="width: 130px; text-align: right;">操作</th>
                    </tr>
                </thead>
                <tbody id="nav-tbody">
                    <!-- 动态生成 -->
                </tbody>
            </table>
        </div>
        <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 10px; line-height: 1.5;">
            💡 站内链接填写相对路径（如 <code>/</code>、<code>/?cate=1</code>、<code>/about</code>），外部链接请填写完整链接（如 <code>https://github.com</code>）。
        </div>
    </div>

    <!-- 模块 2：页脚全局友情链接管理 -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                </div>
                <div>
                    <h3 class="card-title">页脚友情链接管理 (Footer Links)</h3>
                    <p class="card-desc">配置展示在网站每篇文章及详情底部的博友链接与技术站点引荐</p>
                </div>
            </div>
            <div>
                <button type="button" onclick="addFriendLinkRow()" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.84rem;">
                    + 新增友情链接
                </button>
            </div>
        </div>

        <div style="overflow-x: auto; border: 1px solid var(--admin-border); border-radius: var(--radius);">
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">序号</th>
                        <th style="width: 200px;">网站名称 <span style="color:#ef4444;">*</span></th>
                        <th style="width: 280px;">站点链接 (URL) <span style="color:#ef4444;">*</span></th>
                        <th>站点简介 / 悬浮说明 (Tooltip)</th>
                        <th style="width: 140px;">打开方式</th>
                        <th style="width: 130px; text-align: right;">操作</th>
                    </tr>
                </thead>
                <tbody id="friend-links-tbody">
                    <!-- 动态生成 -->
                </tbody>
            </table>
        </div>
        <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 10px; line-height: 1.5;">
            💡 友情链接将展示在文章正文底部的版权信息上方，支持鼠标悬浮展示站点描述，默认以新窗口打开。
        </div>
    </div>
</form>

<script>
const SYSTEM_CATEGORIES = <?= json_encode($categories ?? [], JSON_UNESCAPED_UNICODE) ?>;
let navList = <?= json_encode($customNavList, JSON_UNESCAPED_UNICODE) ?>;
let friendLinksList = <?= json_encode($friendLinksList, JSON_UNESCAPED_UNICODE) ?>;

/* ==========================================================================
   顶栏导航管理器
   ========================================================================== */
function renderNavTable() {
    const tbody = document.getElementById('nav-tbody');
    if (!navList || navList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 24px; color: var(--admin-text-muted);">暂无导航按钮，点击上方「+ 新增导航按钮」添加</td></tr>`;
        return;
    }

    tbody.innerHTML = navList.map((item, idx) => `
        <tr data-index="${idx}">
            <td style="text-align: center; color: var(--admin-text-muted); font-weight: 600;">${idx + 1}</td>
            <td>
                <input type="text" class="form-input nav-item-name" style="padding: 6px 10px; font-size: 0.88rem;" 
                       value="${escapeHtml(item.name || '')}" placeholder="例如：全部文章 / 专栏" oninput="updateNavData(${idx}, 'name', this.value)" required>
            </td>
            <td>
                <input type="text" class="form-input nav-item-url" style="padding: 6px 10px; font-size: 0.88rem;" 
                       value="${escapeHtml(item.url || '')}" placeholder="例如：/ 或 /?cate=1 或 https://..." oninput="updateNavData(${idx}, 'url', this.value)" required>
            </td>
            <td>
                <select class="form-select nav-item-target" style="padding: 6px 10px; font-size: 0.85rem;" onchange="updateNavData(${idx}, 'target', this.value)">
                    <option value="_self" ${item.target === '_self' ? 'selected' : ''}>当前窗口</option>
                    <option value="_blank" ${item.target === '_blank' ? 'selected' : ''}>新窗口打开</option>
                </select>
            </td>
            <td style="text-align: right; white-space: nowrap;">
                <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem;" onclick="moveNavRow(${idx}, -1)" ${idx === 0 ? 'disabled' : ''} title="上移">↑</button>
                <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem;" onclick="moveNavRow(${idx}, 1)" ${idx === navList.length - 1 ? 'disabled' : ''} title="下移">↓</button>
                <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem; color: #ef4444;" onclick="deleteNavRow(${idx})" title="删除">✕</button>
            </td>
        </tr>
    `).join('');
}

function updateNavData(idx, key, val) {
    if (navList[idx]) navList[idx][key] = val;
}

function addNavRow() {
    navList.push({ name: '新导航', url: '/', target: '_self' });
    renderNavTable();
}

function deleteNavRow(idx) {
    navList.splice(idx, 1);
    renderNavTable();
}

function moveNavRow(idx, dir) {
    const targetIdx = idx + dir;
    if (targetIdx < 0 || targetIdx >= navList.length) return;
    const temp = navList[idx];
    navList[idx] = navList[targetIdx];
    navList[targetIdx] = temp;
    renderNavTable();
}

function loadCategoriesToNav() {
    if (SYSTEM_CATEGORIES && SYSTEM_CATEGORIES.length > 0) {
        if (!navList.some(n => n.url === '/')) {
            navList.unshift({ name: '全部文章', url: '/', target: '_self' });
        }
        SYSTEM_CATEGORIES.forEach(cat => {
            const url = '/?cate=' + cat.cate_ID;
            if (!navList.some(n => n.url === url)) {
                navList.push({ name: cat.cate_Name, url: url, target: '_self' });
            }
        });
        renderNavTable();
    }
}

function resetDefaultNav() {
    if (!confirm('确定要恢复默认导航配置吗？')) return;
    navList = [{ name: '全部文章', url: '/', target: '_self' }];
    if (SYSTEM_CATEGORIES && SYSTEM_CATEGORIES.length > 0) {
        SYSTEM_CATEGORIES.forEach(cat => {
            navList.push({ name: cat.cate_Name, url: '/?cate=' + cat.cate_ID, target: '_self' });
        });
    }
    renderNavTable();
}

/* ==========================================================================
   底部友情链接管理器
   ========================================================================== */
function renderFriendLinksTable() {
    const tbody = document.getElementById('friend-links-tbody');
    if (!friendLinksList || friendLinksList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 24px; color: var(--admin-text-muted);">暂无友情链接，点击右上角「+ 新增友情链接」添加</td></tr>`;
        return;
    }

    tbody.innerHTML = friendLinksList.map((item, idx) => `
        <tr data-index="${idx}">
            <td style="text-align: center; color: var(--admin-text-muted); font-weight: 600;">${idx + 1}</td>
            <td>
                <input type="text" class="form-input" style="padding: 6px 10px; font-size: 0.88rem;" 
                       value="${escapeHtml(item.name || '')}" placeholder="站点名称，如 Google" oninput="updateFriendLinkData(${idx}, 'name', this.value)" required>
            </td>
            <td>
                <input type="text" class="form-input" style="padding: 6px 10px; font-size: 0.88rem;" 
                       value="${escapeHtml(item.url || '')}" placeholder="https://..." oninput="updateFriendLinkData(${idx}, 'url', this.value)" required>
            </td>
            <td>
                <input type="text" class="form-input" style="padding: 6px 10px; font-size: 0.88rem;" 
                       value="${escapeHtml(item.desc || '')}" placeholder="简短描述/口号..." oninput="updateFriendLinkData(${idx}, 'desc', this.value)">
            </td>
            <td>
                <select class="form-select" style="padding: 6px 10px; font-size: 0.85rem;" onchange="updateFriendLinkData(${idx}, 'target', this.value)">
                    <option value="_blank" ${item.target !== '_self' ? 'selected' : ''}>新窗口打开</option>
                    <option value="_self" ${item.target === '_self' ? 'selected' : ''}>当前窗口</option>
                </select>
            </td>
            <td style="text-align: right; white-space: nowrap;">
                <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem;" onclick="moveFriendLinkRow(${idx}, -1)" ${idx === 0 ? 'disabled' : ''} title="上移">↑</button>
                <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem;" onclick="moveFriendLinkRow(${idx}, 1)" ${idx === friendLinksList.length - 1 ? 'disabled' : ''} title="下移">↓</button>
                <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem; color: #ef4444;" onclick="deleteFriendLinkRow(${idx})" title="删除">✕</button>
            </td>
        </tr>
    `).join('');
}

function updateFriendLinkData(idx, key, val) {
    if (friendLinksList[idx]) friendLinksList[idx][key] = val;
}

function addFriendLinkRow() {
    friendLinksList.push({ name: '', url: 'https://', desc: '', target: '_blank' });
    renderFriendLinksTable();
}

function deleteFriendLinkRow(idx) {
    friendLinksList.splice(idx, 1);
    renderFriendLinksTable();
}

function moveFriendLinkRow(idx, dir) {
    const targetIdx = idx + dir;
    if (targetIdx < 0 || targetIdx >= friendLinksList.length) return;
    const temp = friendLinksList[idx];
    friendLinksList[idx] = friendLinksList[targetIdx];
    friendLinksList[targetIdx] = temp;
    renderFriendLinksTable();
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// 表单提交前序列化 JSON
document.getElementById('navigation-form').addEventListener('submit', function() {
    document.getElementById('custom_nav_input').value = JSON.stringify(navList);
    document.getElementById('friend_links_input').value = JSON.stringify(friendLinksList);
});

// 初始化渲染表格
renderNavTable();
renderFriendLinksTable();
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
