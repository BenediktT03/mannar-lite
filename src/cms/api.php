 <?php
// Now, let's create the actual API endpoints file:

// api.php: API Endpoints
header('Content-Type: application/json');

// Include configuration
require_once 'config-loader.php';

// CORS handling
function handleCors() {
    // Get API settings
    $settingsDoc = getDocument('system', 'api_settings');
    $allowedOrigins = '*'; // Default to all origins
    
    if ($settingsDoc && isset($settingsDoc['cors']['allowedOrigins'])) {
        $allowedOrigins = $settingsDoc['cors']['allowedOrigins'];
    }
    
    // Set CORS headers
    if ($allowedOrigins === '*') {
        header('Access-Control-Allow-Origin: *');
    } else {
        $origins = explode(',', $allowedOrigins);
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        if (in_array($origin, $origins)) {
            header("Access-Control-Allow-Origin: $origin");
        }
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

// API authentication
function authenticateRequest() {
    // Get API key from header
    $apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
    
    if (empty($apiKey)) {
        http_response_code(401);
        echo json_encode(['error' => 'API key is required']);
        exit;
    }
    
    // Get API settings
    $settingsDoc = getDocument('system', 'api_settings');
    
    // Check if API is enabled
    if (!$settingsDoc || !isset($settingsDoc['enabled']) || !$settingsDoc['enabled']) {
        http_response_code(403);
        echo json_encode(['error' => 'API is disabled']);
        exit;
    }
    
    // Get API keys
    $keyPrefix = substr($apiKey, 0, 8);
    $keyHash = hashApiKey($apiKey);
    
    $apiKeys = queryDocuments('api_keys', [
        ['keyPrefix', '==', $keyPrefix]
    ]);
    
    // Find matching key
    $validKey = null;
    foreach ($apiKeys as $key) {
        if ($key['keyHash'] === $keyHash) {
            $validKey = $key;
            break;
        }
    }
    
    if (!$validKey) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
    
    // Check if key is expired
    $expiryDate = new DateTime($validKey['createdAt']);
    $expiryDate->modify("+{$validKey['expiryDays']} days");
    
    if ($expiryDate < new DateTime()) {
        http_response_code(401);
        echo json_encode(['error' => 'API key has expired']);
        exit;
    }
    
    // Check endpoint permission
    $endpoint = getEndpoint();
    
    if (!isset($validKey['scopes'][$endpoint]) || !$validKey['scopes'][$endpoint]) {
        http_response_code(403);
        echo json_encode(['error' => "API key doesn't have access to the {$endpoint} endpoint"]);
        exit;
    }
    
    // Check request method permission
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET' && !$validKey['permissions']['read']) {
        http_response_code(403);
        echo json_encode(['error' => "API key doesn't have read permission"]);
        exit;
    }
    
    if (($method === 'POST' || $method === 'PUT' || $method === 'PATCH') && !$validKey['permissions']['write']) {
        http_response_code(403);
        echo json_encode(['error' => "API key doesn't have write permission"]);
        exit;
    }
    
    if ($method === 'DELETE' && !$validKey['permissions']['delete']) {
        http_response_code(403);
        echo json_encode(['error' => "API key doesn't have delete permission"]);
        exit;
    }
    
    // Update last used timestamp
    updateDocument('api_keys', array_keys($apiKeys)[0], [
        'lastUsed' => (new DateTime())->format('c')
    ]);
    
    // Check rate limit
    checkRateLimit($apiKey, $settingsDoc['rateLimit'] ?? 60);
    
    return $validKey;
}

// Rate limiting
function checkRateLimit($apiKey, $limit) {
    // Skip rate limiting if limit is 0
    if ($limit <= 0) {
        return;
    }
    
    $cacheKey = 'rate_limit_' . md5($apiKey);
    $cacheFile = sys_get_temp_dir() . '/' . $cacheKey;
    
    // Get current minute
    $currentMinute = floor(time() / 60);
    
    // Get cached data
    $cacheData = [];
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
    }
    
    // Initialize or reset count for new minute
    if (!isset($cacheData['minute']) || $cacheData['minute'] !== $currentMinute) {
        $cacheData = [
            'minute' => $currentMinute,
            'count' => 0
        ];
    }
    
    // Increment request count
    $cacheData['count']++;
    
    // Save cache
    file_put_contents($cacheFile, json_encode($cacheData));
    
    // Check if limit exceeded
    if ($cacheData['count'] > $limit) {
        http_response_code(429);
        echo json_encode([
            'error' => 'Rate limit exceeded',
            'limit' => $limit,
            'reset' => 60 - (time() % 60) // Seconds until next minute
        ]);
        exit;
    }
    
    // Add rate limit headers
    header("X-Rate-Limit-Limit: $limit");
    header("X-Rate-Limit-Remaining: " . ($limit - $cacheData['count']));
    header("X-Rate-Limit-Reset: " . (60 - (time() % 60)));
}

// Helper function to hash API key
function hashApiKey($apiKey) {
    return hash('sha256', $apiKey);
}

// Helper function to get endpoint from URL
function getEndpoint() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    
    // Remove "api" from path if present
    if ($parts[0] === 'api') {
        array_shift($parts);
    }
    
    return $parts[0] ?? '';
}

// Helper function to get ID from URL
function getResourceId() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    
    // Remove "api" from path if present
    if ($parts[0] === 'api') {
        array_shift($parts);
    }
    
    return $parts[1] ?? null;
}

// Parse query parameters
function getQueryParams() {
    $params = [];
    
    // Page and per_page
    $params['page'] = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $params['per_page'] = isset($_GET['per_page']) ? min((int)$_GET['per_page'], 100) : 10;
    
    // Search
    if (isset($_GET['search'])) {
        $params['search'] = $_GET['search'];
    }
    
    // Category
    if (isset($_GET['category'])) {
        $params['category'] = $_GET['category'];
    }
    
    // Tag
    if (isset($_GET['tag'])) {
        $params['tag'] = $_GET['tag'];
    }
    
    // Status (admin only)
    if (isset($_GET['status'])) {
        $params['status'] = $_GET['status'];
    }
    
    return $params;
}

// Parse request body
function getRequestBody() {
    $body = file_get_contents('php://input');
    return json_decode($body, true) ?? [];
}

// Handle GET request
function handleGet($endpoint, $id = null) {
    switch ($endpoint) {
        case 'posts':
            return $id ? getPost($id) : getPosts();
        
        case 'pages':
            return $id ? getPage($id) : getPages();
        
        case 'categories':
            return $id ? getCategory($id) : getCategories();
        
        case 'tags':
            return $id ? getTag($id) : getTags();
        
        case 'media':
            return $id ? getMediaItem($id) : getMedia();
        
        case 'users':
            return $id ? getUser($id) : getUsers();
        
        case 'comments':
            return $id ? getComment($id) : getComments();
        
        default:
            http_response_code(404);
            return ['error' => 'Endpoint not found'];
    }
}

// Handle POST request
function handlePost($endpoint, $data) {
    switch ($endpoint) {
        case 'posts':
            return createPost($data);
        
        case 'pages':
            return createPage($data);
        
        case 'categories':
            return createCategory($data);
        
        case 'tags':
            return createTag($data);
        
        case 'media':
            return uploadMedia();
        
        case 'comments':
            return createComment($data);
        
        default:
            http_response_code(404);
            return ['error' => 'Endpoint not found'];
    }
}

// Handle PUT request
function handlePut($endpoint, $id, $data) {
    if (!$id) {
        http_response_code(400);
        return ['error' => 'ID is required'];
    }
    
    switch ($endpoint) {
        case 'posts':
            return updatePost($id, $data);
        
        case 'pages':
            return updatePage($id, $data);
        
        case 'categories':
            return updateCategory($id, $data);
        
        case 'tags':
            return updateTag($id, $data);
        
        case 'comments':
            return updateComment($id, $data);
        
        default:
            http_response_code(404);
            return ['error' => 'Endpoint not found'];
    }
}

// Handle DELETE request
function handleDelete($endpoint, $id) {
    if (!$id) {
        http_response_code(400);
        return ['error' => 'ID is required'];
    }
    
    switch ($endpoint) {
        case 'posts':
            return deletePost($id);
        
        case 'pages':
            return deletePage($id);
        
        case 'categories':
            return deleteCategory($id);
        
        case 'tags':
            return deleteTag($id);
        
        case 'media':
            return deleteMedia($id);
        
        case 'comments':
            return deleteComment($id);
        
        default:
            http_response_code(404);
            return ['error' => 'Endpoint not found'];
    }
}

// Get posts
function getPosts() {
    $params = getQueryParams();
    $page = $params['page'];
    $perPage = $params['per_page'];
    
    // Build query
    $queryConditions = [
        ['status', '==', 'published']
    ];
    
    // Add category filter
    if (isset($params['category'])) {
        $queryConditions[] = ['category', '==', $params['category']];
    }
    
    // Add tag filter
    if (isset($params['tag'])) {
        $queryConditions[] = ['tags', 'array-contains', $params['tag']];
    }
    
    // Execute query with pagination
    $posts = queryDocuments('posts', $queryConditions, 'publishedAt', 'desc', $perPage, ($page - 1) * $perPage);
    
    // Get total count
    $total = countDocuments('posts', $queryConditions);
    
    // Format response
    $response = [
        'data' => [],
        'meta' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ]
    ];
    
    // Format posts
    foreach ($posts as $id => $post) {
        $response['data'][] = formatPost($id, $post);
    }
    
    return $response;
}

// Get single post
function getPost($id) {
    $post = getDocument('posts', $id);
    
    if (!$post) {
        http_response_code(404);
        return ['error' => 'Post not found'];
    }
    
    // Check if post is published
    if ($post['status'] !== 'published') {
        http_response_code(404);
        return ['error' => 'Post not found'];
    }
    
    return formatPost($id, $post);
}

// Create post
function createPost($data) {
    // Validate required fields
    if (empty($data['title']) || empty($data['content'])) {
        http_response_code(400);
        return ['error' => 'Title and content are required'];
    }
    
    // Generate slug if not provided
    if (empty($data['slug'])) {
        $data['slug'] = createSlug($data['title']);
    }
    
    // Format tags as array
    if (isset($data['tags']) && !is_array($data['tags'])) {
        $data['tags'] = explode(',', $data['tags']);
        $data['tags'] = array_map('trim', $data['tags']);
    }
    
    // Set default values
    $post = [
        'title' => $data['title'],
        'content' => $data['content'],
        'excerpt' => $data['excerpt'] ?? '',
        'slug' => $data['slug'],
        'category' => $data['category'] ?? '',
        'tags' => $data['tags'] ?? [],
        'featuredImage' => $data['featured_image'] ?? null,
        'status' => $data['status'] ?? 'draft',
        'authorId' => 'api_user',
        'authorName' => 'API',
        'createdAt' => (new DateTime())->format('c'),
        'updatedAt' => (new DateTime())->format('c')
    ];
    
    // Add publishedAt if status is published
    if ($post['status'] === 'published') {
        $post['publishedAt'] = (new DateTime())->format('c');
    }
    
    // Save to Firestore
    $id = addDocument('posts', $post);
    
    return [
        'id' => $id,
        'message' => 'Post created successfully'
    ];
}

// Update post
function updatePost($id, $data) {
    // Get existing post
    $post = getDocument('posts', $id);
    
    if (!$post) {
        http_response_code(404);
        return ['error' => 'Post not found'];
    }
    
    // Generate slug if title changed and slug not provided
    if (isset($data['title']) && $data['title'] !== $post['title'] && !isset($data['slug'])) {
        $data['slug'] = createSlug($data['title']);
    }
    
    // Format tags as array
    if (isset($data['tags']) && !is_array($data['tags'])) {
        $data['tags'] = explode(',', $data['tags']);
        $data['tags'] = array_map('trim', $data['tags']);
    }
    
    // Add publishedAt if status changed to published
    if (isset($data['status']) && $data['status'] === 'published' && $post['status'] !== 'published') {
        $data['publishedAt'] = (new DateTime())->format('c');
    }
    
    // Update updatedAt
    $data['updatedAt'] = (new DateTime())->format('c');
    
    // Update post
    updateDocument('posts', $id, $data);
    
    return [
        'id' => $id,
        'message' => 'Post updated successfully'
    ];
}

// Delete post
function deletePost($id) {
    // Check if post exists
    $post = getDocument('posts', $id);
    
    if (!$post) {
        http_response_code(404);
        return ['error' => 'Post not found'];
    }
    
    // Delete post
    deleteDocument('posts', $id);
    
    return [
        'message' => 'Post deleted successfully'
    ];
}

// Format post for API response
function formatPost($id, $post) {
    return [
        'id' => $id,
        'title' => $post['title'],
        'content' => $post['content'],
        'excerpt' => $post['excerpt'] ?? '',
        'slug' => $post['slug'],
        'author' => $post['authorName'] ?? 'Unknown',
        'category' => $post['category'] ?? '',
        'tags' => $post['tags'] ?? [],
        'featured_image' => $post['featuredImage'] ?? null,
        'status' => $post['status'] ?? 'draft',
        'created_at' => $post['createdAt'] ?? null,
        'updated_at' => $post['updatedAt'] ?? null,
        'published_at' => $post['publishedAt'] ?? null
    ];
}

// Similar implementation for pages, categories, tags, media, users, comments
// ...

// Create slug
function createSlug($text) {
    // Convert to lowercase
    $text = strtolower($text);
    
    // Remove special characters
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Replace spaces with hyphens
    $text = preg_replace('/\s+/', '-', $text);
    
    // Remove duplicate hyphens
    $text = preg_replace('/-+/', '-', $text);
    
    // Trim hyphens from beginning and end
    return trim($text, '-');
}

// Main API router
handleCors();
$apiKey = authenticateRequest();

$endpoint = getEndpoint();
$id = getResourceId();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $response = handleGet($endpoint, $id);
        break;
    
    case 'POST':
        $data = getRequestBody();
        $response = handlePost($endpoint, $data);
        break;
    
    case 'PUT':
        $data = getRequestBody();
        $response = handlePut($endpoint, $id, $data);
        break;
    
    case 'DELETE':
        $response = handleDelete($endpoint, $id);
        break;
    
    default:
        http_response_code(405);
        $response = ['error' => 'Method not allowed'];
}

echo json_encode($response);
?>