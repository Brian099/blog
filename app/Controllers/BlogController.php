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
        $postId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $keyword = trim($_GET['q'] ?? '');

        // 获取左侧年份文章树
        $tree = Post::getYearGroupedTree($cateId, $tagId, $keyword);
        
        // 计算文章总数
        $totalArticles = 0;
        foreach ($tree as $year => $posts) {
            $totalArticles += count($posts);
        }

        // 如果没有指定 postId，默认取列表第一篇
        if (!$postId) {
            $postId = Post::getFirstPostId($cateId, $tagId);
        }

        $currentPost = null;
        if ($postId) {
            $currentPost = Post::getDetail($postId);
            if ($currentPost) {
                Post::incrementViews($postId);
            }
        }

        // 获取全部分类与热门标签供导航使用
        $categories = Category::getAll();
        $tags = Tag::getAll();
        $siteSettings = Setting::getAll();

        // 选中的分类或标签信息
        $activeCategory = $cateId ? Category::getById($cateId) : null;
        $activeTag = $tagId ? Tag::getById($tagId) : null;

        require VIEW_PATH . '/index.php';
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
