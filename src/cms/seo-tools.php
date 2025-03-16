 <?php
// Include configuration
require_once 'config-loader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin or editor
function isAdminOrEditor() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    return $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor';
}

// Redirect if not admin or editor
if (!isAdminOrEditor()) {
    header('Location: login.php');
    exit;
}

// Function to analyze content readability
function analyzeReadability($content) {
    // Strip HTML tags
    $text = strip_tags($content);
    
    // Word count
    $wordCount = str_word_count($text);
    
    // Sentence count (rough estimation)
    $sentenceCount = preg_match_all('/[.!?](?!\w)/', $text, $matches);
    $sentenceCount = max(1, $sentenceCount); // Prevent division by zero
    
    // Average words per sentence
    $avgWordsPerSentence = round($wordCount / $sentenceCount, 1);
    
    // Flesch-Kincaid Reading Ease score (simplified)
    $fleschScore = 206.835 - (1.015 * ($wordCount / $sentenceCount)) - (84.6 * (substr_count($text, ' ') / $wordCount));
    $fleschScore = max(0, min(100, $fleschScore)); // Keep between 0-100
    
    // Determine readability level
    $readabilityLevel = 'Unknown';
    if ($fleschScore >= 90) {
        $readabilityLevel = 'Very Easy';
    } elseif ($fleschScore >= 80) {
        $readabilityLevel = 'Easy';
    } elseif ($fleschScore >= 70) {
        $readabilityLevel = 'Fairly Easy';
    } elseif ($fleschScore >= 60) {
        $readabilityLevel = 'Standard';
    } elseif ($fleschScore >= 50) {
        $readabilityLevel = 'Fairly Difficult';
    } elseif ($fleschScore >= 30) {
        $readabilityLevel = 'Difficult';
    } else {
        $readabilityLevel = 'Very Difficult';
    }
    
    return [
        'wordCount' => $wordCount,
        'sentenceCount' => $sentenceCount,
        'avgWordsPerSentence' => $avgWordsPerSentence,
        'fleschScore' => round($fleschScore, 1),
        'readabilityLevel' => $readabilityLevel
    ];
}

// Function to analyze keyword density
function analyzeKeywordDensity($content, $keyword) {
    if (empty($keyword)) {
        return [
            'density' => 0,
            'count' => 0,
            'status' => 'not-set'
        ];
    }
    
    // Strip HTML tags
    $text = strip_tags($content);
    
    // Count words
    $wordCount = str_word_count($text);
    
    // Count keyword occurrences (case insensitive)
    $keywordCount = substr_count(strtolower($text), strtolower($keyword));
    
    // Calculate density
    $density = ($wordCount > 0) ? ($keywordCount / $wordCount) * 100 : 0;
    
    // Determine status
    $status = 'good';
    if ($density < 0.5) {
        $status = 'low';
    } elseif ($density > 3) {
        $status = 'high';
    }
    
    return [
        'density' => round($density, 2),
        'count' => $keywordCount,
        'status' => $status
    ];
}

// Function to analyze meta description
function analyzeMetaDescription($description) {
    $length = strlen($description);
    $status = 'good';
    $message = 'Your meta description has a good length.';
    
    if ($length == 0) {
        $status = 'missing';
        $message = 'You should add a meta description.';
    } elseif ($length < 100) {
        $status = 'short';
        $message = 'Your meta description is too short (under 100 characters).';
    } elseif ($length > 160) {
        $status = 'long';
        $message = 'Your meta description is too long (over 160 characters).';
    }
    
    return [
        'length' => $length,
        'status' => $status,
        'message' => $message
    ];
}

// Function to analyze title
function analyzeTitle($title) {
    $length = strlen($title);
    $status = 'good';
    $message = 'Your title has a good length.';
    
    if ($length == 0) {
        $status = 'missing';
        $message = 'You should add a title.';
    } elseif ($length < 30) {
        $status = 'short';
        $message = 'Your title is too short (under 30 characters).';
    } elseif ($length > 60) {
        $status = 'long';
        $message = 'Your title is too long (over 60 characters).';
    }
    
    return [
        'length' => $length,
        'status' => $status,
        'message' => $message
    ];
}

// Function to process content analysis
function analyzeContent($title, $content, $metaDescription, $keyword) {
    $results = [];
    
    // Analyze title
    $results['title'] = analyzeTitle($title);
    
    // Analyze meta description
    $results['metaDescription'] = analyzeMetaDescription($metaDescription);
    
    // Analyze readability
    $results['readability'] = analyzeReadability($content);
    
    // Analyze keyword density
    $results['keywordDensity'] = analyzeKeywordDensity($content, $keyword);
    
    // Check if keyword appears in title
    $results['keywordInTitle'] = (stripos($title, $keyword) !== false);
    
    // Check if keyword appears in meta description
    $results['keywordInMeta'] = (stripos($metaDescription, $keyword) !== false);
    
    // Check if keyword appears in first paragraph
    $firstParagraph = '';
    if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $content, $matches)) {
        $firstParagraph = strip_tags($matches[0]);
    }
    $results['keywordInFirstParagraph'] = (stripos($firstParagraph, $keyword) !== false);
    
    // Check for headings
    $results['hasHeadings'] = (preg_match('/<h[1-6][^>]*>.*?<\/h[1-6]>/is', $content) > 0);
    
    // Check for images
    $results['hasImages'] = (preg_match('/<img[^>]*>/is', $content) > 0);
    
    // Count outbound links
    $results['outboundLinks'] = preg_match_all('/<a[^>]*href=["\']https?:\/\/(?!'. preg_quote($_SERVER['HTTP_HOST'], '/') .')[^"\']*["\'][^>]*>/i', $content, $matches);
    
    // Count internal links
    $results['internalLinks'] = preg_match_all('/<a[^>]*href=["\'](?!https?:\/\/)(?!javascript:)[^"\']*["\'][^>]*>/i', $content, $matches);
    
    // Overall score calculation
    $score = 0;
    
    // Title score
    if ($results['title']['status'] === 'good') $score += 10;
    elseif ($results['title']['status'] === 'short' || $results['title']['status'] === 'long') $score += 5;
    
    // Meta description score
    if ($results['metaDescription']['status'] === 'good') $score += 10;
    elseif ($results['metaDescription']['status'] === 'short' || $results['metaDescription']['status'] === 'long') $score += 5;
    
    // Keyword in title, meta, first paragraph
    if ($results['keywordInTitle']) $score += 10;
    if ($results['keywordInMeta']) $score += 10;
    if ($results['keywordInFirstParagraph']) $score += 10;
    
    // Keyword density
    if ($results['keywordDensity']['status'] === 'good') $score += 10;
    elseif ($results['keywordDensity']['status'] === 'low' || $results['keywordDensity']['status'] === 'high') $score += 5;
    
    // Readability score
    if ($results['readability']['fleschScore'] >= 60) $score += 10;
    elseif ($results['readability']['fleschScore'] >= 30) $score += 5;
    
    // Structure elements
    if ($results['hasHeadings']) $score += 10;
    if ($results['hasImages']) $score += 10;
    if ($results['internalLinks'] > 0) $score += 5;
    if ($results['outboundLinks'] > 0) $score += 5;
    
    $results['overallScore'] = min(100, $score);
    
    return $results;
}

// Initialize variables
$contentId = isset($_GET['id']) ? $_GET['id'] : '';
$contentType = isset($_GET['type']) ? $_GET['type'] : 'post';
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$title = '';
$content = '';
$metaDescription = '';
$results = null;

// Load content if ID is provided
if (!empty($contentId)) {
    $contentData = getDocument($contentType . 's', $contentId);
    
    if ($contentData) {
        $title = $contentData['title'] ?? '';
        $content = $contentData['content'] ?? '';
        $metaDescription = $contentData['metaDescription'] ?? '';
        
        // Analyze content
        $results = analyzeContent($title, $content, $metaDescription, $keyword);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Tools - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .status-good {
            background-color: #28a745;
        }
        
        .status-warning {
            background-color: #ffc107;
        }
        
        .status-poor {
            background-color: #dc3545;
        }
        
        .status-neutral {
            background-color: #6c757d;
        }
        
        .score-gauge {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            border-radius: 50%;
            background: conic-gradient(
                var(--score-color) 0% var(--score-percent),
                #f0f0f0 var(--score-percent) 100%
            );
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .score-gauge::before {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;
            background-color: white;
            border-radius: 50%;
            z-index: 1;
        }
        
        .score-value {
            position: relative;
            z-index: 2;
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .score-text {
            text-align: center;
            margin-top: 1rem;
            font-weight: bold;
        }
        
        .seo-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .seo-card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        
        .seo-card-title {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .seo-suggestions {
            margin-top: 1rem;
        }
        
        .seo-suggestion {
            display: flex;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .seo-suggestion:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .seo-suggestion-icon {
            flex: 0 0 20px;
            margin-right: 10px;
        }
        
        .seo-suggestion-content {
            flex: 1;
        }
        
        .seo-suggestion-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .seo-tab-content {
            display: none;
        }
        
        .seo-tab-content.active {
            display: block;
        }
        
        .seo-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 1.5rem;
        }
        
        .seo-tab {
            padding: 0.75rem 1rem;
            cursor: pointer;
        }
        
        .seo-tab.active {
            border-bottom: 2px solid #0077b6;
            font-weight: bold;
        }
        
        .seo-data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .seo-data-table th, .seo-data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .seo-data-table th {
            background-color: #f8f9fa;
        }
        
        .keyword-form {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">PHP Firebase CMS</div>
            <ul>
                <li><a href="index.php">View Site</a></li>
                <li><a href="admin.php">Admin</a></li>
                <li><a href="seo-tools.php" class="active">SEO Tools</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>SEO Analysis Tools</h1>
            
            <?php if (empty($contentId)): ?>
                <!-- Content selection form -->
                <div class="seo-card">
                    <div class="seo-card-header">
                        <h2 class="seo-card-title">Select Content to Analyze</h2>
                    </div>
                    <div id="content-selection">
                        <p>Choose content to analyze for SEO optimization:</p>
                        
                        <!-- Content will be loaded via JavaScript -->
                        <div class="tab-container">
                            <div class="tab-nav">
                                <button class="tab-btn active" data-tab="posts">Posts</button>
                                <button class="tab-btn" data-tab="pages">Pages</button>
                            </div>
                            <div class="tab-content">
                                <div id="posts-tab" class="tab-pane active">
                                    <p>Loading posts...</p>
                                </div>
                                <div id="pages-tab" class="tab-pane">
                                    <p>Loading pages...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sitemap Tools -->
                <div class="seo-card">
                    <div class="seo-card-header">
                        <h2 class="seo-card-title">Sitemap Tools</h2>
                    </div>
                    <p>Generate and manage your website's XML sitemap:</p>
                    <div style="margin-top: 1rem;">
                        <a href="generate-sitemap.php" class="btn btn-primary">Generate Sitemap</a>
                        <?php if (file_exists('sitemap.xml')): ?>
                            <a href="sitemap.xml" target="_blank" class="btn">View Sitemap</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Robots.txt Editor -->
                <div class="seo-card">
                    <div class="seo-card-header">
                        <h2 class="seo-card-title">Robots.txt Editor</h2>
                    </div>
                    <p>Edit your website's robots.txt file to control search engine crawling:</p>
                    <form id="robots-form">
                        <div class="form-group">
                            <textarea id="robots-content" rows="10" class="code-editor"><?php 
                                echo file_exists('robots.txt') ? htmlspecialchars(file_get_contents('robots.txt')) : "User-agent: *\nAllow: /\n\n# Disallow admin pages\nDisallow: /admin.php\nDisallow: /login.php\nDisallow: /register.php\n\n# Sitemap location\nSitemap: " . $siteUrl . "/sitemap.xml";
                            ?></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save robots.txt</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Content analysis -->
                <div class="keyword-form">
                    <form action="seo-tools.php" method="get">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($contentId); ?>">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($contentType); ?>">
                        <div class="form-group" style="display: flex; gap: 10px;">
                            <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Enter focus keyword" style="flex: 1;">
                            <button type="submit" class="btn btn-primary">Analyze</button>
                            <a href="seo-tools.php" class="btn">Back to SEO Tools</a>
                        </div>
                    </form>
                </div>
                
                <?php if ($results): ?>
                    <div class="seo-tabs">
                        <div class="seo-tab active" data-tab="analysis">Analysis</div>
                        <div class="seo-tab" data-tab="suggestions">Suggestions</div>
                        <div class="seo-tab" data-tab="data">Content Data</div>
                    </div>
                    
                    <!-- Analysis Tab -->
                    <div id="analysis-tab" class="seo-tab-content active">
                        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                            <!-- Overall Score Card -->
                            <div class="seo-card" style="flex: 1; min-width: 250px;">
                                <div class="seo-card-header">
                                    <h2 class="seo-card-title">Overall SEO Score</h2>
                                </div>
                                <?php
                                    $score = $results['overallScore'];
                                    $scoreColor = '#dc3545'; // Red
                                    $scoreText = 'Poor';
                                    
                                    if ($score >= 80) {
                                        $scoreColor = '#28a745'; // Green
                                        $scoreText = 'Good';
                                    } elseif ($score >= 60) {
                                        $scoreColor = '#ffc107'; // Yellow
                                        $scoreText = 'Needs Improvement';
                                    } elseif ($score >= 40) {
                                        $scoreColor = '#fd7e14'; // Orange
                                        $scoreText = 'Poor';
                                    }
                                ?>
                                <div class="score-gauge" style="--score-percent: <?php echo $score; ?>%; --score-color: <?php echo $scoreColor; ?>">
                                    <div class="score-value"><?php echo $score; ?></div>
                                </div>
                                <div class="score-text" style="color: <?php echo $scoreColor; ?>"><?php echo $scoreText; ?></div>
                            </div>
                            
                            <!-- Content Metrics Card -->
                            <div class="seo-card" style="flex: 1; min-width: 250px;">
                                <div class="seo-card-header">
                                    <h2 class="seo-card-title">Content Metrics</h2>
                                </div>
                                <table class="seo-data-table">
                                    <tr>
                                        <td>Word Count</td>
                                        <td>
                                            <?php 
                                                $wordCount = $results['readability']['wordCount'];
                                                $wordCountStatus = 'status-poor';
                                                if ($wordCount >= 300) $wordCountStatus = 'status-good';
                                                elseif ($wordCount >= 200) $wordCountStatus = 'status-warning';
                                            ?>
                                            <span class="status-indicator <?php echo $wordCountStatus; ?>"></span>
                                            <?php echo $wordCount; ?> words
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Readability</td>
                                        <td>
                                            <?php 
                                                $readability = $results['readability']['readabilityLevel'];
                                                $fleschScore = $results['readability']['fleschScore'];
                                                $readabilityStatus = 'status-neutral';
                                                
                                                if ($fleschScore >= 70) $readabilityStatus = 'status-good';
                                                elseif ($fleschScore >= 50) $readabilityStatus = 'status-warning';
                                                elseif ($fleschScore < 50) $readabilityStatus = 'status-poor';
                                            ?>
                                            <span class="status-indicator <?php echo $readabilityStatus; ?>"></span>
                                            <?php echo $readability; ?> (Score: <?php echo $fleschScore; ?>)
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Keyword Density</td>
                                        <td>
                                            <?php 
                                                $density = $results['keywordDensity']['density'];
                                                $densityStatus = 'status-good';
                                                
                                                if ($density == 0) $densityStatus = 'status-poor';
                                                elseif ($density < 0.5) $densityStatus = 'status-warning';
                                                elseif ($density > 3) $densityStatus = 'status-warning';
                                            ?>
                                            <span class="status-indicator <?php echo $densityStatus; ?>"></span>
                                            <?php echo $density; ?>% (<?php echo $results['keywordDensity']['count']; ?> occurrences)
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Title</td>
                                        <td>
                                            <?php 
                                                $titleStatus = $results['title']['status'];
                                                $titleLength = $results['title']['length'];
                                                $titleStatusClass = 'status-good';
                                                
                                                if ($titleStatus == 'missing') $titleStatusClass = 'status-poor';
                                                elseif ($titleStatus == 'short' || $titleStatus == 'long') $titleStatusClass = 'status-warning';
                                            ?>
                                            <span class="status-indicator <?php echo $titleStatusClass; ?>"></span>
                                            <?php echo $titleLength; ?> characters
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Meta Description</td>
                                        <td>
                                            <?php 
                                                $metaStatus = $results['metaDescription']['status'];
                                                $metaLength = $results['metaDescription']['length'];
                                                $metaStatusClass = 'status-good';
                                                
                                                if ($metaStatus == 'missing') $metaStatusClass = 'status-poor';
                                                elseif ($metaStatus == 'short' || $metaStatus == 'long') $metaStatusClass = 'status-warning';
                                            ?>
                                            <span class="status-indicator <?php echo $metaStatusClass; ?>"></span>
                                            <?php echo $metaLength; ?> characters
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- SEO Checklist Card -->
                            <div class="seo-card" style="flex: 2; min-width: 300px;">
                                <div class="seo-card-header">
                                    <h2 class="seo-card-title">SEO Checklist</h2>
                                </div>
                                <table class="seo-data-table">
                                    <tr>
                                        <td>Keyword in Title</td>
                                        <td>
                                            <?php if ($results['keywordInTitle']): ?>
                                                <span class="status-indicator status-good"></span> Yes
                                            <?php else: ?>
                                                <span class="status-indicator status-poor"></span> No
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Keyword in Meta Description</td>
                                        <td>
                                            <?php if ($results['keywordInMeta']): ?>
                                                <span class="status-indicator status-good"></span> Yes
                                            <?php else: ?>
                                                <span class="status-indicator status-poor"></span> No
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Keyword in First Paragraph</td>
                                        <td>
                                            <?php if ($results['keywordInFirstParagraph']): ?>
                                                <span class="status-indicator status-good"></span> Yes
                                            <?php else: ?>
                                                <span class="status-indicator status-poor"></span> No
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Headings</td>
                                        <td>
                                            <?php if ($results['hasHeadings']): ?>
                                                <span class="status-indicator status-good"></span> Yes
                                            <?php else: ?>
                                                <span class="status-indicator status-poor"></span> No
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Images</td>
                                        <td>
                                            <?php if ($results['hasImages']): ?>
                                                <span class="status-indicator status-good"></span> Yes
                                            <?php else: ?>
                                                <span class="status-indicator status-warning"></span> No
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Internal Links</td>
                                        <td>
                                            <?php
                                                $internalLinks = $results['internalLinks'];
                                                $internalLinksStatus = 'status-warning';
                                                if ($internalLinks >= 3) $internalLinksStatus = 'status-good';
                                                elseif ($internalLinks >= 1) $internalLinksStatus = 'status-warning';
                                                else $internalLinksStatus = 'status-poor';
                                            ?>
                                            <span class="status-indicator <?php echo $internalLinksStatus; ?>"></span>
                                            <?php echo $internalLinks; ?> links
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Outbound Links</td>
                                        <td>
                                            <?php
                                                $outboundLinks = $results['outboundLinks'];
                                                $outboundLinksStatus = 'status-warning';
                                                if ($outboundLinks >= 2) $outboundLinksStatus = 'status-good';
                                                elseif ($outboundLinks >= 1) $outboundLinksStatus = 'status-warning';
                                                else $outboundLinksStatus = 'status-poor';
                                            ?>
                                            <span class="status-indicator <?php echo $outboundLinksStatus; ?>"></span>
                                            <?php echo $outboundLinks; ?> links
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Suggestions Tab -->
                    <div id="suggestions-tab" class="seo-tab-content">
                        <div class="seo-card">
                            <div class="seo-card-header">
                                <h2 class="seo-card-title">SEO Improvement Suggestions</h2>
                            </div>
                            <div class="seo-suggestions">
                                <?php
                                // Title suggestions
                                if ($results['title']['status'] === 'missing') {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">⚠️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add a title</div>
                                            <div>Your content is missing a title. Adding a descriptive title is crucial for SEO.</div>
                                        </div>
                                    </div>';
                                } elseif ($results['title']['status'] === 'short') {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Improve your title</div>
                                            <div>Your title is too short (' . $results['title']['length'] . ' characters). Consider making it more descriptive for better SEO.</div>
                                        </div>
                                    </div>';
                                } elseif ($results['title']['status'] === 'long') {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Shorten your title</div>
                                            <div>Your title is too long (' . $results['title']['length'] . ' characters). Search engines typically display the first 50-60 characters.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Keyword in title suggestion
                                if (!$results['keywordInTitle'] && !empty($keyword)) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add keyword to title</div>
                                            <div>Your focus keyword "' . htmlspecialchars($keyword) . '" does not appear in the title. Adding it can improve your SEO ranking.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Meta description suggestions
                                if ($results['metaDescription']['status'] === 'missing') {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">⚠️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add a meta description</div>
                                            <div>Your content is missing a meta description. This is important for SEO and how your page appears in search results.</div>
                                        </div>
                                    </div>';
                                } elseif ($results['metaDescription']['status'] === 'short') {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Improve your meta description</div>
                                            <div>Your meta description is too short (' . $results['metaDescription']['length'] . ' characters). Aim for 120-160 characters for better search results display.</div>
                                        </div>
                                    </div>';
                                } elseif ($results['metaDescription']['status'] === 'long') {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Shorten your meta description</div>
                                            <div>Your meta description is too long (' . $results['metaDescription']['length'] . ' characters). Search engines typically display 120-160 characters.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Keyword in meta suggestion
                                if (!$results['keywordInMeta'] && !empty($keyword)) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add keyword to meta description</div>
                                            <div>Your focus keyword "' . htmlspecialchars($keyword) . '" does not appear in the meta description.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Content length suggestion
                                if ($results['readability']['wordCount'] < 300) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add more content</div>
                                            <div>Your content is relatively short (' . $results['readability']['wordCount'] . ' words). For better SEO, aim for at least 300 words.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Keyword density suggestion
                                if ($results['keywordDensity']['status'] === 'low' && !empty($keyword)) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Increase keyword density</div>
                                            <div>Your keyword density is only ' . $results['keywordDensity']['density'] . '%. Try to use your focus keyword "' . htmlspecialchars($keyword) . '" more often in your content.</div>
                                        </div>
                                    </div>';
                                } elseif ($results['keywordDensity']['status'] === 'high' && !empty($keyword)) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">⚠️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Reduce keyword density</div>
                                            <div>Your keyword density is ' . $results['keywordDensity']['density'] . '%, which may be seen as keyword stuffing. Try to use your focus keyword more naturally.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Keyword in first paragraph suggestion
                                if (!$results['keywordInFirstParagraph'] && !empty($keyword)) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add keyword to first paragraph</div>
                                            <div>Your focus keyword "' . htmlspecialchars($keyword) . '" does not appear in the first paragraph. Adding it can improve your SEO ranking.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Headings suggestion
                                if (!$results['hasHeadings']) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add headings</div>
                                            <div>Your content does not have any headings. Using h2, h3, etc. helps structure your content and improves readability and SEO.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Images suggestion
                                if (!$results['hasImages']) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add images</div>
                                            <div>Your content does not have any images. Adding relevant images can make your content more engaging and improve SEO.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Internal links suggestion
                                if ($results['internalLinks'] == 0) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add internal links</div>
                                            <div>Your content does not have any internal links. Adding links to other pages on your site improves SEO and user navigation.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Outbound links suggestion
                                if ($results['outboundLinks'] == 0) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Add outbound links</div>
                                            <div>Your content does not have any outbound links. Linking to high-quality external sources can improve your content\'s credibility.</div>
                                        </div>
                                    </div>';
                                }
                                
                                // Readability suggestion
                                if ($results['readability']['fleschScore'] < 60) {
                                    echo '<div class="seo-suggestion">
                                        <div class="seo-suggestion-icon">ℹ️</div>
                                        <div class="seo-suggestion-content">
                                            <div class="seo-suggestion-title">Improve readability</div>
                                            <div>Your content\'s readability score is ' . $results['readability']['fleschScore'] . ' (' . $results['readability']['readabilityLevel'] . '). Try using shorter sentences and simpler language.</div>
                                        </div>
                                    </div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Data Tab -->
                    <div id="data-tab" class="seo-tab-content">
                        <div class="seo-card">
                            <div class="seo-card-header">
                                <h2 class="seo-card-title">Content Data</h2>
                            </div>
                            <h3>Title</h3>
                            <div style="margin-bottom: 1rem; padding: 0.75rem; background-color: #f8f9fa; border-radius: 4px;">
                                <?php echo htmlspecialchars($title); ?>
                            </div>
                            
                            <h3>Meta Description</h3>
                            <div style="margin-bottom: 1rem; padding: 0.75rem; background-color: #f8f9fa; border-radius: 4px;">
                                <?php echo htmlspecialchars($metaDescription); ?>
                            </div>
                            
                            <h3>Focus Keyword</h3>
                            <div style="margin-bottom: 1rem; padding: 0.75rem; background-color: #f8f9fa; border-radius: 4px;">
                                <?php echo !empty($keyword) ? htmlspecialchars($keyword) : '<em>No focus keyword set</em>'; ?>
                            </div>
                            
                            <h3>Content Preview</h3>
                            <div style="margin-bottom: 1rem; padding: 0.75rem; background-color: #f8f9fa; border-radius: 4px; max-height: 300px; overflow-y: auto;">
                                <?php echo $content; ?>
                            </div>
                            
                            <div style="margin-top: 1rem;">
                                <a href="editor.php?id=<?php echo htmlspecialchars($contentId); ?>&type=<?php echo htmlspecialchars($contentType); ?>" class="btn btn-primary">Edit Content</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> PHP Firebase CMS</p>
    </footer>

    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase functions
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, collection, doc, getDoc, setDoc, getDocs, query, where, orderBy } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

        // Firebase configuration
        const firebaseConfig = {
            apiKey: "<?php echo FIREBASE_API_KEY; ?>",
            authDomain: "<?php echo FIREBASE_AUTH_DOMAIN; ?>",
            projectId: "<?php echo FIREBASE_PROJECT_ID; ?>",
            storageBucket: "<?php echo FIREBASE_STORAGE_BUCKET; ?>",
            messagingSenderId: "<?php echo FIREBASE_MESSAGING_SENDER_ID; ?>",
            appId: "<?php echo FIREBASE_APP_ID; ?>",
            measurementId: "<?php echo FIREBASE_MEASUREMENT_ID; ?>"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const db = getFirestore(app);
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            if (user) {
                // Set up logout functionality
                document.getElementById('logoutBtn').addEventListener('click', () => {
                    signOut(auth).then(() => {
                        // Sign-out successful
                        window.location.href = 'index.php';
                    }).catch((error) => {
                        // An error happened
                        console.error('Logout error:', error);
                    });
                });
                
                <?php if (empty($contentId)): ?>
                    // Load content lists
                    loadPosts();
                    loadPages();
                    
                    // Setup tab navigation
                    setupTabs();
                    
                    // Setup robots.txt form
                    setupRobotsForm();
                <?php else: ?>
                    // Setup SEO tabs
                    setupSeoTabs();
                <?php endif; ?>
            }
        });
        
        <?php if (empty($contentId)): ?>
            // Load posts
            async function loadPosts() {
                try {
                    const postsTab = document.getElementById('posts-tab');
                    
                    // Create query
                    const postsQuery = query(
                        collection(db, "posts"),
                        orderBy("title")
                    );
                    
                    const postsSnapshot = await getDocs(postsQuery);
                    
                    if (postsSnapshot.empty) {
                        postsTab.innerHTML = '<p>No posts found.</p>';
                        return;
                    }
                    
                    let postsHTML = '<div class="form-group"><input type="text" id="posts-search" placeholder="Search posts..."></div>';
                    postsHTML += '<table>';
                    postsHTML += '<tr><th>Title</th><th>Status</th><th>Actions</th></tr>';
                    
                    postsSnapshot.forEach((doc) => {
                        const post = doc.data();
                        postsHTML += `
                            <tr>
                                <td>${post.title || 'Untitled'}</td>
                                <td>${post.status || 'draft'}</td>
                                <td>
                                    <a href="seo-tools.php?id=${doc.id}&type=post" class="btn btn-primary">Analyze</a>
                                </td>
                            </tr>
                        `;
                    });
                    
                    postsHTML += '</table>';
                    postsTab.innerHTML = postsHTML;
                    
                    // Setup search functionality
                    document.getElementById('posts-search').addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase();
                        const rows = postsTab.querySelectorAll('table tr');
                        
                        // Skip header row
                        for (let i = 1; i < rows.length; i++) {
                            const title = rows[i].cells[0].textContent.toLowerCase();
                            if (title.includes(searchTerm)) {
                                rows[i].style.display = '';
                            } else {
                                rows[i].style.display = 'none';
                            }
                        }
                    });
                    
                } catch (error) {
                    console.error("Error loading posts:", error);
                    document.getElementById('posts-tab').innerHTML = '<p>Error loading posts. Please try again.</p>';
                }
            }
            
            // Load pages
            async function loadPages() {
                try {
                    const pagesTab = document.getElementById('pages-tab');
                    
                    // Create query
                    const pagesQuery = query(
                        collection(db, "pages"),
                        orderBy("title")
                    );
                    
                    const pagesSnapshot = await getDocs(pagesQuery);
                    
                    if (pagesSnapshot.empty) {
                        pagesTab.innerHTML = '<p>No pages found.</p>';
                        return;
                    }
                    
                    let pagesHTML = '<div class="form-group"><input type="text" id="pages-search" placeholder="Search pages..."></div>';
                    pagesHTML += '<table>';
                    pagesHTML += '<tr><th>Title</th><th>Status</th><th>Actions</th></tr>';
                    
                    pagesSnapshot.forEach((doc) => {
                        const page = doc.data();
                        pagesHTML += `
                            <tr>
                                <td>${page.title || 'Untitled'}</td>
                                <td>${page.status || 'draft'}</td>
                                <td>
                                    <a href="seo-tools.php?id=${doc.id}&type=page" class="btn btn-primary">Analyze</a>
                                </td>
                            </tr>
                        `;
                    });
                    
                    pagesHTML += '</table>';
                    pagesTab.innerHTML = pagesHTML;
                    
                    // Setup search functionality
                    document.getElementById('pages-search').addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase();
                        const rows = pagesTab.querySelectorAll('table tr');
                        
                        // Skip header row
                        for (let i = 1; i < rows.length; i++) {
                            const title = rows[i].cells[0].textContent.toLowerCase();
                            if (title.includes(searchTerm)) {
                                rows[i].style.display = '';
                            } else {
                                rows[i].style.display = 'none';
                            }
                        }
                    });
                    
                } catch (error) {
                    console.error("Error loading pages:", error);
                    document.getElementById('pages-tab').innerHTML = '<p>Error loading pages. Please try again.</p>';
                }
            }
            
            // Setup tab navigation
            function setupTabs() {
                const tabButtons = document.querySelectorAll('.tab-btn');
                const tabPanes = document.querySelectorAll('.tab-pane');
                
                tabButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        // Update active button
                        tabButtons.forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                        
                        // Show selected tab
                        const tabId = button.getAttribute('data-tab');
                        tabPanes.forEach(pane => pane.classList.remove('active'));
                        document.getElementById(`${tabId}-tab`).classList.add('active');
                    });
                });
            }
            
            // Setup robots.txt form
            function setupRobotsForm() {
                document.getElementById('robots-form').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    const robotsContent = document.getElementById('robots-content').value;
                    
                    try {
                        // Send AJAX request to save robots.txt
                        const response = await fetch('save-robots.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'content=' + encodeURIComponent(robotsContent)
                        });
                        
                        if (response.ok) {
                            alert('robots.txt saved successfully!');
                        } else {
                            alert('Error saving robots.txt. Please try again.');
                        }
                    } catch (error) {
                        console.error("Error saving robots.txt:", error);
                        alert('Error saving robots.txt. Please try again.');
                    }
                });
            }
        <?php else: ?>
            // Setup SEO tabs
            function setupSeoTabs() {
                const seoTabs = document.querySelectorAll('.seo-tab');
                const seoTabContents = document.querySelectorAll('.seo-tab-content');
                
                seoTabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        // Update active tab
                        seoTabs.forEach(t => t.classList.remove('active'));
                        tab.classList.add('active');
                        
                        // Show selected tab content
                        const tabId = tab.getAttribute('data-tab');
                        seoTabContents.forEach(content => content.classList.remove('active'));
                        document.getElementById(`${tabId}-tab`).classList.add('active');
                    });
                });
            }
        <?php endif; ?>
    </script>
</body>
</html>