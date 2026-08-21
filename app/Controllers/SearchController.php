<?php
namespace App\Controllers;

use App\Database;
use App\Helpers;

class SearchController {
    public function search(): void {
        header('Content-Type: application/json; charset=utf-8');
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q, 'UTF-8') < 1) {
            echo json_encode(['results' => []]);
            return;
        }

        $sql = "SELECT log_ID, log_Title, log_Content, log_PostTime, log_CateID 
                FROM zbp_post 
                WHERE log_Status = 0 AND log_Type = 0 AND (log_Title LIKE ? OR log_Content LIKE ?) 
                ORDER BY log_PostTime DESC 
                LIMIT 20";
        $rows = Database::query($sql, ["%{$q}%", "%{$q}%"]);

        $results = [];
        foreach ($rows as $row) {
            $snippet = Helpers::getSnippet($row['log_Content'], 90);
            // 高亮关键字
            $highlightTitle = preg_replace('/(' . preg_quote($q, '/') . ')/iu', '<mark>$1</mark>', htmlspecialchars($row['log_Title']));
            $highlightSnippet = preg_replace('/(' . preg_quote($q, '/') . ')/iu', '<mark>$1</mark>', htmlspecialchars($snippet));

            $results[] = [
                'id' => (int)$row['log_ID'],
                'title' => $row['log_Title'],
                'title_highlight' => $highlightTitle,
                'snippet_highlight' => $highlightSnippet,
                'date' => Helpers::formatDate((int)$row['log_PostTime'], 'Y-m-d')
            ];
        }

        echo json_encode(['results' => $results]);
    }
}
