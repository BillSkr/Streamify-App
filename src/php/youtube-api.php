<?php
require_once 'config.php';

class YouTubeAPI {
    private $apiKey;
    private $baseUrl = 'https://www.googleapis.com/youtube/v3/';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Search for videos on YouTube
     */
    public function searchVideos($query, $maxResults = 10) {
        $url = $this->baseUrl . 'search?' . http_build_query([
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => $maxResults,
            'key' => $this->apiKey
        ]);
        
        $response = $this->makeRequest($url);
        
        if ($response === false) {
            return ['error' => 'Σφάλμα κατά την αναζήτηση στο YouTube'];
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            return ['error' => 'YouTube API Error: ' . $data['error']['message']];
        }
        
        return $this->formatSearchResults($data);
    }
    
    /**
     * Get video details by ID
     */
    public function getVideoDetails($videoId) {
        $url = $this->baseUrl . 'videos?' . http_build_query([
            'part' => 'snippet,contentDetails,statistics',
            'id' => $videoId,
            'key' => $this->apiKey
        ]);
        
        $response = $this->makeRequest($url);
        
        if ($response === false) {
            return ['error' => 'Σφάλμα κατά την ανάκτηση λεπτομερειών βίντεο'];
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            return ['error' => 'YouTube API Error: ' . $data['error']['message']];
        }
        
        if (empty($data['items'])) {
            return ['error' => 'Το βίντεο δεν βρέθηκε'];
        }
        
        return $this->formatVideoDetails($data['items'][0]);
    }
    
    /**
     * Extract video ID from YouTube URL
     */
    public static function extractVideoId($url) {
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        // If it's already a video ID
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }
        
        return false;
    }
    
    /**
     * Validate if a YouTube video exists and is accessible
     */
    public function validateVideo($videoId) {
        $details = $this->getVideoDetails($videoId);
        return !isset($details['error']);
    }
    
    /**
     * Make HTTP request to YouTube API
     */
    private function makeRequest($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Streamify/1.0'
            ]
        ]);
        
        return @file_get_contents($url, false, $context);
    }
    
    /**
     * Format search results for display
     */
    private function formatSearchResults($data) {
        $results = [];
        
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $results[] = [
                    'id' => $item['id']['videoId'],
                    'title' => $item['snippet']['title'],
                    'description' => $item['snippet']['description'],
                    'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? $item['snippet']['thumbnails']['default']['url'],
                    'channel' => $item['snippet']['channelTitle'],
                    'published' => $item['snippet']['publishedAt'],
                    'url' => 'https://www.youtube.com/watch?v=' . $item['id']['videoId']
                ];
            }
        }
        
        return [
            'results' => $results,
            'totalResults' => $data['pageInfo']['totalResults'] ?? 0
        ];
    }
    
    /**
     * Format video details
     */
    private function formatVideoDetails($item) {
        return [
            'id' => $item['id'],
            'title' => $item['snippet']['title'],
            'description' => $item['snippet']['description'],
            'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? $item['snippet']['thumbnails']['default']['url'],
            'channel' => $item['snippet']['channelTitle'],
            'published' => $item['snippet']['publishedAt'],
            'duration' => $this->formatDuration($item['contentDetails']['duration'] ?? ''),
            'views' => $item['statistics']['viewCount'] ?? 0,
            'likes' => $item['statistics']['likeCount'] ?? 0,
            'url' => 'https://www.youtube.com/watch?v=' . $item['id']
        ];
    }
    
    /**
     * Convert YouTube duration format (PT4M13S) to readable format
     */
    private function formatDuration($duration) {
        if (empty($duration)) return '';
        
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);
        
        $hours = isset($matches[1]) ? intval($matches[1]) : 0;
        $minutes = isset($matches[2]) ? intval($matches[2]) : 0;
        $seconds = isset($matches[3]) ? intval($matches[3]) : 0;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
    }
}

// API Helper functions
function searchYouTubeVideos($query, $maxResults = 10) {
    if (empty(YOUTUBE_API_KEY) || YOUTUBE_API_KEY === 'YOUR_YOUTUBE_API_KEY_HERE') {
        return ['error' => 'YouTube API Key δεν έχει ρυθμιστεί'];
    }
    
    $youtube = new YouTubeAPI(YOUTUBE_API_KEY);
    return $youtube->searchVideos($query, $maxResults);
}

function getYouTubeVideoDetails($videoId) {
    if (empty(YOUTUBE_API_KEY) || YOUTUBE_API_KEY === 'YOUR_YOUTUBE_API_KEY_HERE') {
        return ['error' => 'YouTube API Key δεν έχει ρυθμιστεί'];
    }
    
    $youtube = new YouTubeAPI(YOUTUBE_API_KEY);
    return $youtube->getVideoDetails($videoId);
}

function validateYouTubeVideo($videoId) {
    if (empty(YOUTUBE_API_KEY) || YOUTUBE_API_KEY === 'YOUR_YOUTUBE_API_KEY_HERE') {
        return false;
    }
    
    $youtube = new YouTubeAPI(YOUTUBE_API_KEY);
    return $youtube->validateVideo($videoId);
}

// AJAX endpoint for video search
if (isset($_GET['action']) && $_GET['action'] === 'search' && !empty($_GET['q'])) {
    header('Content-Type: application/json');
    
    $query = sanitizeInput($_GET['q']);
    $maxResults = min(20, max(1, intval($_GET['max'] ?? 10)));
    
    $results = searchYouTubeVideos($query, $maxResults);
    echo json_encode($results);
    exit;
}

// AJAX endpoint for video details
if (isset($_GET['action']) && $_GET['action'] === 'details' && !empty($_GET['id'])) {
    header('Content-Type: application/json');
    
    $videoId = sanitizeInput($_GET['id']);
    $details = getYouTubeVideoDetails($videoId);
    echo json_encode($details);
    exit;
}
?>