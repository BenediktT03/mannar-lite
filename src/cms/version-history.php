 // version-history.php structure
<?php
require_once 'config-loader.php';

// Store version when content is updated
function saveContentVersion($collectionName, $documentId, $content, $userId) {
    $versionData = [
        'contentId' => $documentId,
        'collection' => $collectionName,
        'content' => $content,
        'userId' => $userId,
        'createdAt' => new Date().toISOString()
    ];
    
    return addDoc(collection(db, "versions"), $versionData);
}

// Fetch version history
function getContentVersions($collectionName, $documentId, $limit = 10) {
    $versionsQuery = query(
        collection(db, "versions"),
        where("contentId", "==", $documentId),
        where("collection", "==", $collectionName),
        orderBy("createdAt", "desc"),
        limit($limit)
    );
    
    return getDocs($versionsQuery);
}

// Restore content from version
function restoreVersion($versionId) {
    // Get version data
    // Update the original document
}