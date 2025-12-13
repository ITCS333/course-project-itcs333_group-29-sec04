<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    sendResponse(["success" => false,"message" => "Unauthorized"],401);
}
// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Send JSON response and exit
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Sanitize string input to prevent XSS
 */
function sanitizeInput($data) {
    if (!is_string($data)) return $data;
    $data = trim($data);
    $data = strip_tags($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate resource name (topics or replies)
 */
function isValidResource($resource) {
    $allowed = ['topics', 'replies'];
    return in_array($resource, $allowed);
}

// ============================================================================
// HEADERS & CORS
// ============================================================================

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

require_once "../../config/Database.php";
$database = new Database();
$db = $database->getConnection();

// Get HTTP method and input data
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

/**
 * Fetch all topics (optionally filtered and sorted)
 */
function getAllTopics($db) {
    $search = isset($_GET['search']) ? "%".sanitizeInput($_GET['search'])."%" : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : "created_at";
    $order = isset($_GET['order']) && strtolower($_GET['order']) === "asc" ? "ASC" : "DESC";

    $allowedSort = ['subject', 'author', 'created_at'];
    if (!in_array($sort, $allowedSort)) $sort = 'created_at';

    $sql = "SELECT * FROM topics";
    $params = [];
    if ($search) {
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params[':search'] = $search;
    }
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    if ($search) $stmt->bindParam(':search', $params[':search']);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $result]);
}

/**
 * Fetch single topic by ID
 */
function getTopicById($db, $id) {
    if (!$id) sendResponse(['error' => 'Topic ID is required'], 400);
    $stmt = $db->prepare("SELECT * FROM topics WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($topic) sendResponse(['success' => true, 'data' => $topic]);
    else sendResponse(['error' => 'Topic not found'], 404);
}

/**
 * Create new topic
 */
function createTopic($db, $data) {
    $subject = sanitizeInput($data['subject'] ?? '');
    $message = sanitizeInput($data['message'] ?? '');
    $author = sanitizeInput($data['author'] ?? '');
    if (!$subject || !$message || !$author) sendResponse(['error'=>'Missing required fields'], 400);

    $stmt = $db->prepare("INSERT INTO topics (subject,message,author) VALUES (:subject,:message,:author)");
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':author', $author);
    $stmt->execute();
    $id = $db->lastInsertId();
    sendResponse(['success'=>true,'topic_id'=>$id], 201);
}

/**
 * Update existing topic by ID
 */
function updateTopic($db, $data) {
    $id = $data['id'] ?? null;
    if (!$id) sendResponse(['error'=>'Topic ID is required'], 400);

    $stmt = $db->prepare("SELECT * FROM topics WHERE id = :id");
    $stmt->bindParam(':id',$id);
    $stmt->execute();
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['error'=>'Topic not found'], 404);

    $fields = [];
    $params = [':id'=>$id];
    if (isset($data['subject'])) { $fields[]='subject=:subject'; $params[':subject']=sanitizeInput($data['subject']); }
    if (isset($data['message'])) { $fields[]='message=:message'; $params[':message']=sanitizeInput($data['message']); }
    if (!$fields) sendResponse(['error'=>'No fields to update'], 400);

    $sql = "UPDATE topics SET ".implode(', ',$fields)." WHERE id=:id";
    $stmt = $db->prepare($sql);
    foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->execute();
    sendResponse(['success'=>true,'message'=>'Topic updated']);
}

/**
 * Delete topic by ID (also deletes associated replies)
 */
function deleteTopic($db, $id) {
    if (!$id) sendResponse(['error'=>'Topic ID is required'],400);
    $stmt = $db->prepare("SELECT * FROM topics WHERE id=:id");
    $stmt->bindParam(':id',$id);
    $stmt->execute();
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['error'=>'Topic not found'],404);

    $stmt = $db->prepare("DELETE FROM replies WHERE topic_id=:id");
    $stmt->bindParam(':id',$id);
    $stmt->execute();

    $stmt = $db->prepare("DELETE FROM topics WHERE id=:id");
    $stmt->bindParam(':id',$id);
    $stmt->execute();
    sendResponse(['success'=>true,'message'=>'Topic deleted']);
}

// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

/**
 * Fetch all replies for a topic
 */
function getRepliesByTopicId($db,$topic_id){
    if (!$topic_id) sendResponse(['error'=>'Topic ID required'],400);
    $stmt = $db->prepare("SELECT * FROM replies WHERE topic_id=:topic_id ORDER BY created_at ASC");
    $stmt->bindParam(':topic_id',$topic_id);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$result]);
}

/**
 * Create new reply
 */
function createReply($db,$data){
    $topic_id = $data['topic_id'] ?? null;
    $text = sanitizeInput($data['text'] ?? '');
    $author = sanitizeInput($data['author'] ?? '');
    if (!$topic_id || !$text || !$author) sendResponse(['error'=>'Missing required fields'],400);

    $stmt = $db->prepare("SELECT * FROM topics WHERE id=:topic_id");
    $stmt->bindParam(':topic_id',$topic_id);
    $stmt->execute();
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['error'=>'Parent topic not found'],404);

    $stmt = $db->prepare("INSERT INTO replies (topic_id,text,author) VALUES (:topic_id,:text,:author)");
    $stmt->bindParam(':topic_id',$topic_id);
    $stmt->bindParam(':text',$text);
    $stmt->bindParam(':author',$author);
    $stmt->execute();
    $id = $db->lastInsertId();
    sendResponse(['success'=>true,'reply_id'=>$id],201);
}

/**
 * Delete reply by ID
 */
function deleteReply($db,$id){
    if (!$id) sendResponse(['error'=>'Reply ID required'],400);
    $stmt = $db->prepare("SELECT * FROM replies WHERE id=:id");
    $stmt->bindParam(':id',$id);
    $stmt->execute();
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['error'=>'Reply not found'],404);

    $stmt = $db->prepare("DELETE FROM replies WHERE id=:id");
    $stmt->bindParam(':id',$id);
    $stmt->execute();
    sendResponse(['success'=>true,'message'=>'Reply deleted']);
}

// ============================================================================
// MAIN ROUTER
// ============================================================================

try {
    $resource = $_GET['resource'] ?? null;
    if (!isValidResource($resource)) sendResponse(['error'=>'Invalid resource'],400);

    switch($resource){
        case 'topics':
            if ($method==='GET'){
                if (isset($_GET['id'])) getTopicById($db,$_GET['id']);
                else getAllTopics($db);
            } elseif ($method==='POST') createTopic($db,$input);
            elseif ($method==='PUT') updateTopic($db,$input);
            elseif ($method==='DELETE') deleteTopic($db,$_GET['id'] ?? null);
            else sendResponse(['error'=>'Method Not Allowed'],405);
            break;

        case 'replies':
            if ($method==='GET') getRepliesByTopicId($db,$_GET['topic_id'] ?? null);
            elseif ($method==='POST') createReply($db,$input);
            elseif ($method==='DELETE') deleteReply($db,$_GET['id'] ?? null);
            else sendResponse(['error'=>'Method Not Allowed'],405);
            break;
    }
}catch(PDOException $e){
    sendResponse(['error'=>'Database error'],500);
}catch(Exception $e){
    sendResponse(['error'=>'Server error'],500);
}
?>
