<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1") (Cancelled)
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments--> (Changed to comments_week)
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type:application/json");
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
    exit();
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
require_once "../../config/Database.php";

// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();

$database=new Database();
$db=$database->getConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method=$_SERVER["REQUEST_METHOD"];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$raw_body=file_get_contents('php://input');
$data=json_decode($raw_body,true);

// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = isset($_GET['resource']) ? $_GET['resource'] : null;

// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $search= $_GET['search'] ?? null;
    $sort= $_GET["sort"] ?? "start_date";
    $order= $_GET["order"] ?? "asc";

    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
    $sql="SELECT id, title, start_date, description, links, created_at FROM weeks";//--> week_id to id

    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
    if(!empty($search)){
        $sql.= " WHERE title LIKE ? OR description LIKE ?";
    }
    
    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
    if(!isValidSortField($sort,["title", "start_date", "created_at"])){
        $sort="start_date";
    }
    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)
    if($order!=="asc" && $order!=="desc"){
        $order="asc";
    }    
    // TODO: Add ORDER BY clause to the query
    $sql.= " ORDER BY $sort $order";
    // TODO: Prepare the SQL query using PDO
    $statement=$db->prepare($sql);
    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
    if(!empty($search)){
        $s="%$search%";
        $statement->bindValue(1,$s);
        $statement->bindValue(2,$s);
    }
    // TODO: Execute the query
    $statement->execute();
    // TODO: Fetch all results as an associative array
    $result=$statement->fetchAll();
    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
    foreach($result as &$row){
        if(!empty($row["links"])){
            $row["links"]=json_decode($row["links"]);
        }
    }
    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
    sendResponse(["success" => true, "data" => $result],200);
}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")------>(Changed to id)
 */
function getWeekById($db, $id) {
    // TODO: Validate that week_id is provided ------>(Changed to id)
    // If not, return error response with 400 status
    if(empty($id)){
        return sendError("id parameter is missing",400);
    }    
    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $sql="SELECT id, title, start_date, description, links, created_at FROM weeks WHERE id = ?";
    $statement=$db->prepare($sql);
    // TODO: Bind the week_id parameter
    $statement->bindValue(1,$id);
    // TODO: Execute the query
    $statement->execute();
    // TODO: Fetch the result
    $result=$statement->fetch();
    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
    if($result){
        $result["links"]=json_decode($result["links"]);
        return sendResponse(["success" => true, "data" => $result]);
    }else{
        return sendError("Week not found",404);
    }
}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")------>(cancelled)
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    
    if(empty($data["title"]) || empty($data["start_date"]) || empty($data["description"])){
        return sendError("Missing required fields",400);
    }
    // TODO: Sanitize input data
    // Trim whitespace from title, description, and week_id
    
    $title=sanitizeInput($data["title"]);
    $description=sanitizeInput($data["description"]);

    // TODO: Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status
    if(!validateDate($data["start_date"])){
        return sendError("Invalid date format",400);
    }
    

    //Since we are removing the id, this is no longer needed:

    // TODO: Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    // $statement=$db->prepare("SELECT * FROM weeks WHERE id = ?");
    // $statement->bindValue(1,$id);
    // $statement->execute();
    // $result=$statement->fetch();
    // if(!empty($result)){
    //     return sendError("Week already exists",409);
    // }
    
    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
    if(isset($data["links"]) && is_array($data["links"])){
        $data["links"]=json_encode($data["links"]);
    }else{
        $data["links"]=json_encode([]);
    }
    // TODO: Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)--> id is canceled here
    $insert_sql="INSERT INTO weeks (title, start_date, description, links) VALUES (?, ?, ?, ?)";
    $statement=$db->prepare($insert_sql);
    // TODO: Bind parameters
    // $statement->bindValue(1,$id);
    $statement->bindValue(1,$title);
    $statement->bindValue(2,$data["start_date"]);
    $statement->bindValue(3,$description);
    $statement->bindValue(4,$data["links"]);
 
    // TODO: Execute the query
    $result=$statement->execute();
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
    if($result){    
        return sendResponse(["success" => true, "data" => $db->lastInsertId()],201);
    }else{
        return sendError("Failed to create week",500);
    }
}


/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)->(Changed to id)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if(empty($data["id"])){
        return sendError("id parameter is missing",400);
    }
    // TODO: Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    $sql="SELECT id, title, start_date, description, links, created_at FROM weeks WHERE id= ?";
    $statement=$db->prepare($sql);
    $statement->execute([$data["id"]]);
    $result=$statement->fetch();
    if(!$result){
        return sendError("Week not found",404);
    }
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $clauses=[];
    $binding_values=[];
    $update_sql="UPDATE weeks SET ";
    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    // If start_date is provided, validate format and add "start_date = ?"
    // If description is provided, add "description = ?"
    // If links is provided, encode to JSON and add "links = ?"
    if(isset($data["title"])){
        $clauses[]="title = ?";
        $binding_values[]=sanitizeInput($data["title"]);
    }
    if(isset($data["start_date"])){
        if(validateDate($data["start_date"])){
            $clauses[]="start_date = ?";
            $binding_values[]=$data["start_date"];
        }
    }
    if(isset($data["description"])){
        $clauses[]="description = ?";
        $binding_values[]=sanitizeInput($data["description"]);
    }
    if(isset($data["links"])){
        $data["links"]=json_encode($data["links"]);
        $clauses[]="links = ?";
        $binding_values[]=$data["links"];
    }
    
    // TODO: If no fields to update, return error response with 400 status
    if(empty($clauses)){
        return sendError("No fields to update",400);
    }
    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
    $clauses[]="updated_at = CURRENT_TIMESTAMP";
    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    foreach($clauses as $c){
        $update_sql.=$c .", ";
    }
    $update_sql = rtrim($update_sql, ", ");//removing last comma
    $update_sql.=" WHERE id = ?";
    // TODO: Prepare the query
    $statement=$db->prepare($update_sql);
    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
    $index=1;
    foreach($binding_values as $value){
        $statement->bindValue($index,$value);
        ++$index;
    }
    $statement->bindValue($index,$data["id"]);
    // TODO: Execute the query
    $result=$statement->execute();
    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
    if($result){
        $statement=$db->prepare("SELECT id, title, start_date, description, links FROM weeks WHERE id = ?");
        $statement->bindValue(1, $data["id"]);
        $statement->execute();
        $week=$statement->fetch();
        $week["links"]=json_decode($week["links"]);

        return sendResponse(["success" => true, "data" => $week]);
    }else{
        return sendError("Failed to update week");
    }
}


/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier  -> (changed to id)
 */
function deleteWeek($db, $id) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if(empty($id)){
        return sendError("id parameter is missing",400);
    }
    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $statement=$db->prepare("SELECT id, title, start_date, description, links, created_at FROM weeks WHERE id = ?");
    $statement->bindValue(1,$id);
    $statement->execute();
    $result=$statement->fetch();

    if(!$result){
        return sendError("Week not found",404);
    }
    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?--> (Changed to comments_week)
    $statement=$db->prepare("DELETE FROM comments_week WHERE week_id = ?");
    $statement->bindValue(1,$id);
    // TODO: Execute comment deletion query
    $statement->execute();
    
    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    $statement=$db->prepare("DELETE FROM weeks WHERE id = ?");
    // TODO: Bind the week_id parameter
    $statement->bindValue(1,$id);
    // TODO: Execute the query
    $result=$statement->execute();
    // TODO: Check if delete was successful
    // If yes, return success response with message indicating week and comments deleted
    // If no, return error response with 500 status
    if($result){
        return sendResponse(["success" => true, "message" => "Week and its comments were successfully deleted"]);
    }else{
        return sendError("Failed to delete week",500);
    }

}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if(empty($weekId)){
        return sendError("week_id parameter is missing",400);
    }
    
    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    $sql="SELECT id, week_id, author, text, created_at FROM comments_week WHERE week_id = ? ORDER BY created_at ASC";
    $statement=$db->prepare($sql);
    // TODO: Bind the week_id parameter
    $statement->bindValue(1,$weekId);
    // TODO: Execute the query
    $statement->execute();
    // TODO: Fetch all results as an associative array
    $result=$statement->fetchAll();
    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
    return sendResponse(["success" => true, "data" => $result]);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    if(empty($data["week_id"]) || empty($data["author"]) || empty($data["text"])){
        return sendError("Missing required fields",400);
    }
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $weekID=$data["week_id"];
    $author=sanitizeInput($data["author"]);
    $text=sanitizeInput($data["text"]);
    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    if(empty($author) || empty($text)){
        return sendError("Missing required fields",400);
    }
    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
    $statement=$db->prepare("SELECT * FROM weeks WHERE id = ?");
    $statement->bindValue(1,$weekID);
    $statement->execute();
    if(!$statement->fetch()){
        return sendError("Week not found",404);
    }
    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
    $insert_sql="INSERT INTO comments_week (week_id, author, text) VALUES (?, ? , ?)";
    $statement=$db->prepare($insert_sql);
    // TODO: Bind parameters
    $statement->bindValue(1,$weekID);
    $statement->bindValue(2,$author);
    $statement->bindValue(3,$text);
        
    // TODO: Execute the query
    $result=$statement->execute();
    // TODO: Check if insert was successful
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
    if($result){
        return sendResponse(["success" => true, "data" => $db->lastInsertId()],201);
    }else{
        return sendError("Failed to create comment",500);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
    if(empty($commentId)){
        return sendError("Missing comment_id",400);
    }
    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $statement=$db->prepare("SELECT * FROM comments_week WHERE id = ?");
    $statement->bindValue(1,$commentId);
    $statement->execute();
    if(!$statement->fetch()){
        return sendError("Comment not found",404);
    }
    
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    $statement=$db->prepare("DELETE FROM comments_week WHERE id = ?");
    // TODO: Bind the id parameter
    $statement->bindValue(1,$commentId);
    
    // TODO: Execute the query
    $result=$statement->execute();
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if($result){
        return sendResponse(["success" => true, "message" => "Comment deleted successfully"]);
    }else{
        return sendError("Failed to delete comment",500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    $resource=$resource??'weeks';
    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
            if(!empty($_GET["id"])){
                getWeekById($db,$_GET["id"]);
            }else{
                getAllWeeks($db);
            }
        } elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
            createWeek($db,$data);
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
            updateWeek($db,$data);
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            deleteWeek($db,$_GET["id"]??($data["id"]??null));
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 Method Not Allowed)
            sendError("Method Not Allowed",405);
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
            getCommentsByWeek($db, $_GET["week_id"]);//-->make sure you are getting it by this name
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            createComment($db,$data);
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            deleteComment($db,$_GET["id"]??($data["id"]??null));
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError("Method Not Allowed",405);            
        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
        sendError("Invalid resource. Use 'weeks' or 'comments'",400);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    error_log($e->getMessage());
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    sendError("Database error occurred",500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
    error_log($e->getMessage());
    sendError("An unexpected error occurred",500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
    http_response_code($statusCode);
    // TODO: Echo JSON encoded data
    // Use json_encode($data)
    echo json_encode($data);
    
    // TODO: Exit to prevent further execution
    exit();
}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    $error=['success' => false, 'error' => $message];
    
    // TODO: Call sendResponse() with the error array and status code
    sendResponse($error,$statusCode);
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    $d=DateTime::createFromFormat('Y-m-d',$date);
    return $d && $d->format('Y-m-d')==$date;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data=trim($data);
    // TODO: Strip HTML tags using strip_tags()
    $data=strip_tags($data);
    // TODO: Convert special characters using htmlspecialchars()
    $data=htmlspecialchars($data);
    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
    return in_array($field,$allowedFields);
}

?>
