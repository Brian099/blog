<?php
namespace App\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Setting;
use App\Helpers;

class BlogController {
    public function index(): void {
        $cateId = isset($_GET['cate']) ? (int)$_GET['cate'] : null;
        $tagId = isset($_GET['tag']) ? (int)$_GET['tag'] : null;
        $postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $keyword = trim($_GET['q'] ?? '');

        // 获取左侧年份文章树
        $tree = Post::getYearGroupedTree($cateId, $tagId, $keyword);
        
        // 计算文章总数
        $totalArticles = 0;
        foreach ($tree as $year => $posts) {
            $totalArticles += count($posts);
        }

        // 指定了 id 才进入文章阅读态，否则展示首页卡片网格
        $currentPost = null;
        if ($postId > 0) {
            $currentPost = Post::getDetail($postId);
            if ($currentPost) {
                Post::incrementViews($postId);
            }
        }
        $viewMode = $currentPost ? 'article' : 'home';

        // 获取全部分类与热门标签供导航使用
        $categories = Category::getAll();
        $tags = Tag::getAll();
        $siteSettings = Setting::getAll();

        // 选中的分类或标签信息
        $activeCategory = $cateId ? Category::getById($cateId) : null;
        $activeTag = $tagId ? Tag::getById($tagId) : null;

        // 首页网格数据：顶部 hero + 2x2 推荐区，以及下方双列最新发布
        $hero = null;
        $features = [];
        $latestPosts = [];
        $topIsPinned = false;

        if ($viewMode === 'home') {
            $pinned = Post::getPinnedPosts($cateId, $tagId, $keyword, 5);
            $topIsPinned = !empty($pinned);
            // 无置顶文章时，退化为展示最新发布的前 5 篇，保证顶部区域不空
            $topPool = $topIsPinned ? $pinned : Post::getLatestPosts($cateId, $tagId, $keyword, 5);

            $hero = array_shift($topPool);
            $features = array_values($topPool);

            $usedIds = [];
            if ($hero) $usedIds[$hero['id']] = true;
            foreach ($features as $item) $usedIds[$item['id']] = true;

            foreach (Post::getLatestPosts($cateId, $tagId, $keyword, 20) as $item) {
                if (isset($usedIds[$item['id']])) continue;
                $latestPosts[] = $item;
                if (count($latestPosts) >= 12) break;
            }
        }

        require VIEW_PATH . '/index.php';
    }

    /**
     * 密码验证与文章解锁 API
     */
    public function unlock(): void {
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_POST['id'] ?? 0);
        $pwd = trim($_POST['password'] ?? '');

        if (!$id || empty($pwd)) {
            echo json_encode(['success' => false, 'message' => '请输入访问密码']);
            return;
        }

        $ok = Post::unlock($id, $pwd);
        if ($ok) {
            $post = Post::getDetail($id, true);

            // 渲染正文区域 HTML
            ob_start();
            require VIEW_PATH . '/partials/post-content.php';
            $html = ob_get_clean();

            $siteTitle = \App\Models\Setting::get('site_name', '技术思维棱镜');
            echo json_encode([
                'success' => true,
                'html' => $html,
                'title' => $post['title'] . ' - ' . $siteTitle
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '访问密码错误，请重试']);
        }
    }

    /**
     * AJAX 局部获取文章内容（用于无刷新秒级切换）
     */
    public function apiGetPost(int $id): void {
        header('Content-Type: application/json; charset=utf-8');
        $post = Post::getDetail($id);
        if (!$post) {
            http_response_code(404);
            echo json_encode(['error' => '文章不存在']);
            return;
        }

        Post::incrementViews($id);

        // 渲染单篇内容 HTML
        ob_start();
        require VIEW_PATH . '/partials/post-content.php';
        $html = ob_get_clean();

        echo json_encode([
            'id' => $post['id'],
            'title' => $post['title'],
            'date' => $post['date_formatted'],
            'read_time' => $post['read_time'],
            'views' => $post['views'] + 1,
            'category' => $post['category'],
            'tags' => $post['tags'],
            'html' => $html
        ]);
    }
}
