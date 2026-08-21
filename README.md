# 极简双栏技术博客系统 (Modern PHP Tech Blog)

一套基于 **PHP 8.x / 7.4+ 单体轻量架构** 重构的高颜值极简双栏技术博客系统，视觉灵感源自 [immarcus.com](https://immarcus.com/blog/)，完美兼容并无缝加载原 Z-Blog 历史数据与附件。

---

## 🌟 核心特性

### 1. 前台沉浸式双栏阅读体验
* **双窗格独立滚动**：左侧年份文章目录树与右侧文章正文完全独立滑动，互不干扰。
* **无刷新秒级切换**：点击左侧文章标题，右侧通过 Fetch/AJAX 极速加载并同步更新浏览器 URL 与历史（支持前进/后退和直接分享 URL）。
* **全语言代码语法高亮**：集成 Highlight.js，支持 VBA、Python、Shell、SQL、JS、PHP 等 30+ 种常用语言，配备 Mac 风格三色圆点顶栏、语言徽章与一键复制代码。
* **图片画廊灯箱**：图片自适应圆角微边框，点击任意图片唤起全屏高清预览（Lightbox）。
* **暗黑 / 浅色双主题**：一键切换，CSS 变量无缝平滑过渡，本地记忆偏好。
* **全局毫秒级即时搜索**：按键盘快捷键 `/` 或点击顶部搜索图标，弹窗即时搜索 620+ 篇技术记录。
* **深度响应式适配**：手机端自动转换为抽屉式侧边栏（Drawer）与防溢出自适应排版。

### 2. 现代化管理后台与智能工具
* **控制台仪表盘 (`/admin`)**：文章总数、分类、标签、附件总数及总存储体积直观统计。
* **经典 UEditor / UEditorPlus 编辑器**：
  * 完美契合原有习惯，全套经典排版工具栏（表格、代码块、格式刷、引用等）；
  * 支持微信/浏览器截图直接 **`Ctrl + V` 粘贴自动上传并落盘**；
  * 支持本地图片拖拽上传与第三方外链图片自动本地化抓取（防外链防盗链失效）。
* **【重点】附件管理与智能清理引擎 (`/admin/media`)**：
  * **智能引用检测**：全站遍历扫描全部文章正文与摘要中的所有图片与附件引用；
  * **孤立文件一键清理**：一键筛选出“未被任何文章引用的无用文件”，支持批量/一键彻底清理物理文件与数据库记录，释放服务器存储。
* **全功能分类与标签管理**：支持可视化增删改查。
* **多数据库适配**：开箱支持便携 SQLite（零依赖秒跑）以及生产 MySQL 一键切换。

---

## 📁 目录结构

```
├── app/
│   ├── Config.php          # 站点配置与数据库设置（SQLite / MySQL 自由切换）
│   ├── Database.php        # 高性能 PDO 驱动
│   ├── Helpers.php         # 宏替换 {#ZC_BLOG_HOST#}、图文排版、阅读时长计算
│   ├── Models/             # Post(文章), Category(分类), Tag(标签), Upload(附件), Setting
│   └── Controllers/        # BlogController(前台), SearchController(搜索), AdminController(后台)
├── views/
│   ├── layouts/            # 布局母版 (Header, Footer)
│   ├── index.php           # 双栏主视图 (左文章树 + 右沉浸图文)
│   ├── partials/           # 文章正文与代码高亮局部模板
│   └── admin/              # 后台管理页面 (仪表盘, 文章, 分类, 附件清理, 设置)
├── public/
│   ├── index.php           # 统一路由入口
│   ├── assets/
│   │   ├── css/style.css   # 前台极简双栏设计系统 (暗黑/浅色)
│   │   ├── css/admin.css   # 后台管理界面样式
│   │   ├── js/app.js       # 无刷新切换、代码高亮复制、灯箱、即时搜索
│   │   ├── js/admin.js     # 后台交互、图片上传、批量删除
│   │   └── ueditor/        # 本地化离线 UEditor 完整资源包
│   └── uploads/            # 附件与图片物理存储目录 (映射 zb_users/upload)
├── data/
│   └── blog.db             # SQLite 数据库文件 (包含全量 620+ 篇文章资产)
├── php.ini                 # PHP 运行配置
└── start_server.bat        # Windows 本地一键启动脚本
```

---

## 🚀 快速启动指南

### 环境要求
* PHP 7.4 或 PHP 8.x
* 开启扩展：`pdo_sqlite`（或 `pdo_mysql`）、`mbstring`、`curl`

### 本地启动
1. **方式一（推荐）**：双击根目录下的 `start_server.bat`
2. **方式二（命令行）**：
   ```bash
   php -c php.ini -S 127.0.0.1:8080 -t public public/index.php
   ```

### 访问地址
* **前台双栏阅读**：`http://127.0.0.1:8080/`
* **管理后台**：`http://127.0.0.1:8080/admin`
  * 默认账号：`admin`
  * 默认密码：`admin123`（可在后台系统设置中修改）

---

## 📄 License
MIT License
