<?php
$pageTitle = '数据备份与转换';
ob_start();
?>

<div class="settings-container" style="max-width: 1080px; margin: 0 auto; padding-bottom: 40px;">
    
    <!-- 1. 顶部数据库运行状态概览卡片 (Hero Card) -->
    <div style="background: #ffffff; border-radius: 14px; padding: 22px 28px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04); margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; position: relative; overflow: hidden;">
        <!-- 顶部装饰渐变条 -->
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #0ea5e9, #38bdf8, #6366f1);"></div>

        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 52px; height: 52px; border-radius: 12px; background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); color: #0284c7; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(14, 165, 233, 0.15); flex-shrink: 0;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 0.85rem; color: #64748b; font-weight: 500;">当前活跃数据库</span>
                    <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em;"><?= strtoupper($driver) ?></h2>
                    <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.75rem; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 2px 10px; border-radius: 9999px; font-weight: 600;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                        已连接运行
                    </span>
                </div>
                <div style="font-size: 0.85rem; color: #475569; margin-top: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <?php if ($driver === 'sqlite'): ?>
                        <span style="color: #64748b;">单文件位置:</span>
                        <code style="background: #f1f5f9; color: #0f172a; padding: 3px 8px; border-radius: 6px; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.82rem; border: 1px solid #e2e8f0;"><?= htmlspecialchars($sqliteInfo['path']) ?></code>
                        <span style="background: #f8fafc; color: #475569; padding: 2px 8px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.78rem; font-weight: 600;">体积: <?= $sqliteInfo['size_formatted'] ?></span>
                    <?php else: ?>
                        <span style="color: #64748b;">连接目标:</span>
                        <code style="background: #f1f5f9; color: #0f172a; padding: 3px 8px; border-radius: 6px; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.82rem; border: 1px solid #e2e8f0;"><?= htmlspecialchars($mysqlConfig['host'] ?? '127.0.0.1') ?>:<?= $mysqlConfig['port'] ?? 3306 ?></code>
                        <span style="color: #64748b;">数据库:</span>
                        <code style="background: #f1f5f9; color: #0f172a; padding: 3px 8px; border-radius: 6px; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.82rem; border: 1px solid #e2e8f0; font-weight: 600;"><?= htmlspecialchars($mysqlConfig['dbname'] ?? '') ?></code>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <button onclick="createBackup()" id="btn-create-bak" class="btn" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; border: none; font-weight: 600; height: 42px; padding: 0 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>创建全新快照备份</span>
            </button>
        </div>
    </div>

    <!-- 2. 模块一：数据库快照备份与历史管理 -->
    <div style="background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.03); margin-bottom: 28px; overflow: hidden;">
        <!-- 卡片头部 -->
        <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; background: #ffffff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    <span>历史备份列表与导出</span>
                </h3>
                <p style="font-size: 0.82rem; color: #64748b; margin: 4px 0 0 0;">
                    备份文件存储在服务器 <code style="background: #f1f5f9; color: #334155; padding: 1px 5px; border-radius: 4px; font-size: 0.78rem;">data/backups/</code> 目录，支持直接下载到本地或一键覆盖还原。
                </p>
            </div>
            <span style="font-size: 0.8rem; background: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 9999px; font-weight: 600; border: 1px solid #e2e8f0;">
                共 <?= count($backups) ?> 个备份文件
            </span>
        </div>

        <!-- 列表内容区 -->
        <?php if (empty($backups)): ?>
            <div style="text-align: center; padding: 48px 24px; color: #64748b;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #f8fafc; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; border: 1px dashed #cbd5e1;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #334155;">暂无历史备份</div>
                <div style="font-size: 0.82rem; color: #94a3b8; margin-top: 4px;">点击右上角的「创建全新快照备份」按钮即可生成第一份备份。</div>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em;">
                            <th style="padding: 12px 24px; font-weight: 600;">备份文件名</th>
                            <th style="padding: 12px 18px; font-weight: 600;">格式类型</th>
                            <th style="padding: 12px 18px; font-weight: 600;">体积大小</th>
                            <th style="padding: 12px 18px; font-weight: 600;">备份时间</th>
                            <th style="padding: 12px 24px; font-weight: 600; text-align: right;">快捷操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $b): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                                <td style="padding: 14px 24px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; border-radius: 8px; background: <?= $b['ext'] === 'sql' ? '#fef3c7' : '#e0e7ff' ?>; color: <?= $b['ext'] === 'sql' ? '#d97706' : '#4f46e5' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <?php if ($b['ext'] === 'sql'): ?>
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                            <?php else: ?>
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <code style="font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.85rem; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($b['filename']) ?></code>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <?php if ($b['ext'] === 'sql'): ?>
                                        <span style="display: inline-block; font-size: 0.75rem; font-weight: 600; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; padding: 2px 8px; border-radius: 6px;">SQL 转储</span>
                                    <?php else: ?>
                                        <span style="display: inline-block; font-size: 0.75rem; font-weight: 600; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 6px;">SQLite 库</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <span style="font-size: 0.88rem; font-weight: 700; color: #0f172a; font-family: ui-monospace, monospace;"><?= $b['size_formatted'] ?></span>
                                </td>
                                <td style="padding: 14px 18px; color: #64748b; font-size: 0.82rem;">
                                    <?= $b['time_formatted'] ?>
                                </td>
                                <td style="padding: 14px 24px; text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                                        <a href="/admin/backup/download?file=<?= urlencode($b['filename']) ?>" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 600; color: #0284c7; background: #f0f9ff; border: 1px solid #bae6fd; padding: 5px 10px; border-radius: 6px; transition: all 0.15s;" title="下载到本地电脑">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                            <span>下载</span>
                                        </a>
                                        <?php if ($b['ext'] === 'db'): ?>
                                            <button onclick="restoreBackup('<?= htmlspecialchars($b['filename']) ?>')" style="cursor: pointer; display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 600; color: #d97706; background: #fffbeb; border: 1px solid #fde68a; padding: 5px 10px; border-radius: 6px; transition: all 0.15s;" title="一键覆盖还原至此备份">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
                                                <span>还原</span>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="deleteBackup('<?= htmlspecialchars($b['filename']) ?>')" style="cursor: pointer; display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 600; color: #e11d48; background: #fff1f2; border: 1px solid #fecdd3; padding: 5px 10px; border-radius: 6px; transition: all 0.15s;" title="删除备份文件">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            <span>删除</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. 模块二：MySQL 一键转 SQLite 便携工具 (仅在当前使用 MySQL 模式时显示) -->
    <?php if ($driver !== 'sqlite'): ?>
        <div style="background: #ffffff; border-radius: 14px; border: 1px solid #fed7aa; box-shadow: 0 4px 16px -2px rgba(245, 158, 11, 0.08); overflow: hidden; position: relative;">
            <!-- 顶部橙色强调线条 -->
            <div style="height: 4px; background: linear-gradient(90deg, #f59e0b, #fbbf24);"></div>

            <div style="padding: 24px 28px;">
                <div style="display: flex; gap: 16px; align-items: flex-start; margin-bottom: 20px;">
                    <div style="width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.15);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line></svg>
                    </div>
                    <div>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <span>一键将 MySQL 迁移转为 SQLite (blog.db)</span>
                            <span style="font-size: 0.75rem; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 2px 8px; border-radius: 6px; font-weight: 600;">便携化免运维</span>
                        </h3>
                        <p style="font-size: 0.85rem; color: #64748b; margin: 6px 0 0 0; line-height: 1.5;">
                            已通过管理员身份验证，系统将<strong>自动从现有配置文件中读取 MySQL 凭据</strong>，一键无损提取全站文章、分类、标签、附件及评论，并自动执行路径规范化生成单文件便携的 <strong><code>data/blog.db</code></strong>。
                        </p>
                    </div>
                </div>

                <!-- 自动识别到的源配置网格卡片 (4 列 Micro-Cards) -->
                <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
                    <div style="font-size: 0.8rem; font-weight: 700; color: #92400e; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <span>已自动识别的源 MySQL 数据库配置：</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
                        <div style="background: #ffffff; padding: 10px 14px; border-radius: 8px; border: 1px solid #fde68a;">
                            <div style="font-size: 0.72rem; color: #92400e; font-weight: 500;">服务器地址</div>
                            <div style="font-size: 0.9rem; font-weight: 700; color: #0f172a; font-family: ui-monospace, monospace; margin-top: 2px;"><?= htmlspecialchars($mysqlConfig['host'] ?? '127.0.0.1') ?>:<?= (int)($mysqlConfig['port'] ?? 3306) ?></div>
                        </div>
                        <div style="background: #ffffff; padding: 10px 14px; border-radius: 8px; border: 1px solid #fde68a;">
                            <div style="font-size: 0.72rem; color: #92400e; font-weight: 500;">数据库名称</div>
                            <div style="font-size: 0.9rem; font-weight: 700; color: #0f172a; font-family: ui-monospace, monospace; margin-top: 2px;"><?= htmlspecialchars($mysqlConfig['dbname'] ?: '未设置(自动探测)') ?></div>
                        </div>
                        <div style="background: #ffffff; padding: 10px 14px; border-radius: 8px; border: 1px solid #fde68a;">
                            <div style="font-size: 0.72rem; color: #92400e; font-weight: 500;">登录用户名</div>
                            <div style="font-size: 0.9rem; font-weight: 700; color: #0f172a; font-family: ui-monospace, monospace; margin-top: 2px;"><?= htmlspecialchars($mysqlConfig['username'] ?? 'root') ?></div>
                        </div>
                        <div style="background: #ffffff; padding: 10px 14px; border-radius: 8px; border: 1px solid #fde68a;">
                            <div style="font-size: 0.72rem; color: #92400e; font-weight: 500;">配置文件源</div>
                            <div style="font-size: 0.9rem; font-weight: 700; color: #0f172a; margin-top: 2px;"><?= htmlspecialchars($config['c_option_source'] ? basename($config['c_option_source']) : 'app/Config.php') ?></div>
                        </div>
                    </div>
                </div>

                <form id="convert-form" onsubmit="handleConvert(event)">
                    <!-- 高级自定义连接折叠区 (选填) -->
                    <div style="margin-bottom: 16px;">
                        <a href="javascript:void(0)" onclick="toggleAdvancedDb()" style="font-size: 0.82rem; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;">
                            <span id="adv-arrow">▶</span> <span>高级选项：自定义指定其他 MySQL 目标连接 (通常无需修改)</span>
                        </a>
                    </div>

                    <div id="advanced-db-fields" style="display: none; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">MySQL 服务器地址 (Host)</label>
                                <input type="text" id="m_host" name="host" class="form-control" value="<?= htmlspecialchars($mysqlConfig['host'] ?? '127.0.0.1') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">端口 (Port)</label>
                                <input type="number" id="m_port" name="port" class="form-control" value="<?= (int)($mysqlConfig['port'] ?? 3306) ?>">
                            </div>
                        </div>

                        <div class="form-grid" style="margin-top: 14px;">
                            <div class="form-group">
                                <label class="form-label">MySQL 数据库名称 (Database Name)</label>
                                <input type="text" id="m_dbname" name="dbname" class="form-control" value="<?= htmlspecialchars($mysqlConfig['dbname'] ?? '') ?>" placeholder="例如: zblog 或 giraff">
                            </div>
                            <div class="form-group">
                                <label class="form-label">MySQL 用户名 (Username)</label>
                                <input type="text" id="m_username" name="username" class="form-control" value="<?= htmlspecialchars($mysqlConfig['username'] ?? 'root') ?>">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 14px;">
                            <label class="form-label">MySQL 密码 (Password)</label>
                            <input type="password" id="m_password" name="password" class="form-control" value="<?= htmlspecialchars($mysqlConfig['password'] ?? '') ?>" placeholder="留空则使用默认配置密码">
                        </div>
                    </div>

                    <div id="convert-result-box" style="display: none; margin-top: 16px; padding: 16px 20px; border-radius: 10px; font-size: 0.9rem; line-height: 1.6;"></div>

                    <div style="margin-top: 20px; display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                        <button type="submit" id="btn-start-convert" class="btn" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: #ffffff; font-weight: 700; height: 42px; padding: 0 22px; border-radius: 8px; border: none; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25); display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            <span>立即从当前配置的 MySQL 转换并生成 SQLite (blog.db)</span>
                        </button>
                        <button type="button" onclick="testMysqlConnection()" id="btn-test-mysql" class="btn" style="height: 42px; padding: 0 18px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.15s;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                            <span>测试当前 MySQL 连通性</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// 折叠/展开高级数据库配置
function toggleAdvancedDb() {
    const el = document.getElementById('advanced-db-fields');
    const arrow = document.getElementById('adv-arrow');
    if (el.style.display === 'none') {
        el.style.display = 'block';
        arrow.innerText = '▼';
    } else {
        el.style.display = 'none';
        arrow.innerText = '▶';
    }
}

// 1. 创建备份
async function createBackup() {
    const btn = document.getElementById('btn-create-bak');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ 正在生成备份...</span>';

    try {
        const res = await fetch('/admin/backup/create', { method: 'POST' });
        const json = await res.json();
        if (json.success) {
            alert(json.message + '\n生成文件: ' + json.filename + ' (' + json.size_formatted + ')');
            location.reload();
        } else {
            alert('备份失败: ' + (json.error || '未知错误'));
            btn.disabled = false;
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg><span>创建全新快照备份</span>';
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg><span>创建全新快照备份</span>';
    }
}

// 2. 删除备份
async function deleteBackup(filename) {
    if (!confirm(`确定要永久删除备份文件 [${filename}] 吗？`)) return;

    const fd = new FormData();
    fd.append('file', filename);

    try {
        const res = await fetch('/admin/backup/delete', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            location.reload();
        } else {
            alert('删除失败: ' + (json.error || '未知原因'));
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
    }
}

// 3. 还原 SQLite 备份
async function restoreBackup(filename) {
    if (!confirm(`⚠️ 危险操作确认：\n\n确定要将当前数据库还原覆盖为备份 [${filename}] 吗？\n系统将在还原前自动备份当前活跃数据库。`)) return;

    const fd = new FormData();
    fd.append('file', filename);

    try {
        const res = await fetch('/admin/backup/restore', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            alert(json.message);
            location.reload();
        } else {
            alert('还原失败: ' + (json.error || '未知原因'));
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
    }
}

// 4. 测试 MySQL 连接
async function testMysqlConnection() {
    const btn = document.getElementById('btn-test-mysql');
    btn.disabled = true;
    btn.innerText = '正在测试连接...';

    const fd = new FormData();
    fd.append('type', 'mysql');
    const hostEl = document.getElementById('m_host');
    if (hostEl && hostEl.value) {
        fd.append('host', document.getElementById('m_host').value);
        fd.append('port', document.getElementById('m_port').value);
        fd.append('dbname', document.getElementById('m_dbname').value);
        fd.append('username', document.getElementById('m_username').value);
        fd.append('password', document.getElementById('m_password').value);
    }

    try {
        const res = await fetch('/install/test-db', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            alert('✅ ' + json.message);
        } else {
            alert('❌ 连接失败: ' + (json.error || '请检查配置'));
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg><span>测试当前 MySQL 连通性</span>';
    }
}

// 5. 执行 MySQL 转 SQLite（直接读取配置，无需重复输入）
async function handleConvert(e) {
    e.preventDefault();
    if (!confirm('确定要从当前配置的 MySQL 数据库完整提取并生成纯净 SQLite (data/blog.db) 吗？\n\n系统将自动完成：\n1. 结构与数据提取\n2. 资源路径规范化\n3. VACUUM 碎片整理与优化\n（若已存在 blog.db，会自动进行安全备份）')) return;

    const btn = document.getElementById('btn-start-convert');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ 正在从配置的 MySQL 迁移数据表并重构 SQLite (请稍候)...</span>';

    const form = document.getElementById('convert-form');
    const fd = new FormData(form);
    const resBox = document.getElementById('convert-result-box');

    try {
        const res = await fetch('/admin/backup/convert-mysql-to-sqlite', { method: 'POST', body: fd });
        const json = await res.json();

        resBox.style.display = 'block';
        if (json.success) {
            const st = json.stats;
            resBox.style.background = '#ecfdf5';
            resBox.style.border = '1px solid #a7f3d0';
            resBox.style.color = '#065f46';
            resBox.innerHTML = `
                <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 8px;">🎉 ${json.message}</div>
                <div style="font-size: 0.85rem; line-height: 1.7;">
                    • 迁移分类: <strong>${st.categories}</strong> 个 | 标签: <strong>${st.tags}</strong> 个<br>
                    • 迁移文章: <strong>${st.posts}</strong> 篇 (已自动规范化路径)<br>
                    • 迁移附件: <strong>${st.uploads}</strong> 个 | 评论: <strong>${st.comments}</strong> 条<br>
                    • 生成 SQLite 文件: <code>${json.sqlite_file}</code> (体积: <strong>${json.sqlite_size_formatted}</strong>)
                </div>
            `;
            alert('转换成功！SQLite 数据库已生成在 data/blog.db');
            setTimeout(() => { location.reload(); }, 2000);
        } else {
            resBox.style.background = '#fef2f2';
            resBox.style.border = '1px solid #fecaca';
            resBox.style.color = '#991b1b';
            resBox.innerHTML = `<strong>转换失败：</strong> ${json.error || '未知错误'}`;
            btn.disabled = false;
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg><span>立即从当前配置的 MySQL 转换并生成 SQLite (blog.db)</span>';
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg><span>立即从当前配置的 MySQL 转换并生成 SQLite (blog.db)</span>';
    }
}
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
