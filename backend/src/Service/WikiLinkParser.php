<?php

namespace App\Service;

class WikiLinkParser
{
    /**
     * Parse wiki-links from markdown content
     * Supports: [[Title]] and [[Title|Alias]]
     * 
     * @param string $content Markdown content
     * @return array Array of link titles (case-preserved)
     */
    public function parseLinks(string $content): array
    {
        $links = [];
        
        // Match [[Title]] or [[Title|Alias]]
        // Pattern: [[ followed by non-bracket content, optionally | and alias, then ]]
        $pattern = '/\[\[([^\]|]+)(?:\|([^\]]+))?\]\]/';
        
        if (preg_match_all($pattern, $content, $matches)) {
            // $matches[1] contains all titles (before |)
            foreach ($matches[1] as $title) {
                $trimmedTitle = trim($title);
                if (!empty($trimmedTitle)) {
                    $links[] = $trimmedTitle;
                }
            }
        }
        
        // Remove duplicates (case-insensitive)
        $links = array_unique(array_map('mb_strtolower', $links));
        
        return array_values($links);
    }
    
    /**
     * Extract link information with aliases
     * Returns array of ['title' => string, 'alias' => string|null]
     * 
     * @param string $content Markdown content
     * @return array
     */
    public function parseLinksWithAliases(string $content): array
    {
        $links = [];
        
        $pattern = '/\[\[([^\]|]+)(?:\|([^\]]+))?\]\]/';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $title = trim($match[1]);
                $alias = isset($match[2]) ? trim($match[2]) : null;
                
                if (!empty($title)) {
                    $links[] = [
                        'title' => $title,
                        'alias' => $alias,
                        'raw' => $match[0]
                    ];
                }
            }
        }
        
        return $links;
    }
}
