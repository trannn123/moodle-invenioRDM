<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();

class pagination_service
{
    private const MAX_VISIBLE_PAGES = 5;

    public static function paginate(array $items, int $page, int $pagesize, \moodle_url $baseurl): array
    {
        $totalitems = count($items);
        $totalpages = max(1, ceil($totalitems / $pagesize));
        $page = max(1, min($page, $totalpages));
        $offset = ($page - 1) * $pagesize;

        $pages = [];

        $half = floor(self::MAX_VISIBLE_PAGES / 2);
        $start = max(1, $page - $half);
        $end = min($totalpages, $start + self::MAX_VISIBLE_PAGES - 1);
        $start = max(1, $end - self::MAX_VISIBLE_PAGES + 1);

        if ($start > 1) {
            $url = clone $baseurl;
            $url->param('page', 1);
            $pages[] = [
                'number' => 1,
                'url' => $url->out(false),
                'active' => ($page == 1)
            ];
        }

        if ($start > 2) {
            $pages[] = [
                'ellipsis' => true
            ];
        }

        for ($i = $start; $i <= $end; $i++) {
            $url = clone $baseurl;
            $url->param('page', $i);
            $pages[] = [
                'number' => $i,
                'url' => $url->out(false),
                'active' => ($page == $i)
            ];
        }

        if ($end < $totalpages - 1) {
            $pages[] = [
                'ellipsis' => true
            ];
        }

        if ($end < $totalpages) {
            $url = clone $baseurl;
            $url->param('page', $totalpages);
            $pages[] = [
                'number' => $totalpages,
                'url' => $url->out(false),
                'active' => ($page == $totalpages)
            ];
        }

        $previous = null;

        if ($page > 1) {
            $url = clone $baseurl;
            $url->param('page', $page - 1);
            $previous = [
                'url' => $url->out(false)
            ];
        }

        $next = null;

        if ($page < $totalpages) {
            $url = clone $baseurl;
            $url->param('page', $page + 1);
            $next = [
                'url' => $url->out(false)
            ];
        }

        return [
            'items' => array_slice($items, $offset, $pagesize),
            'page' => $page,
            'pagesize' => $pagesize,
            'totalitems' => $totalitems,
            'totalpages' => $totalpages,
            'pages' => $pages,
            'previous' => $previous,
            'next' => $next
        ];
    }

}