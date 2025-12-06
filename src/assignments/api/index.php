<?php
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments 
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header('Content-Type: application/json');
// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization'); 
// TODO: Handle preflight OPTIONS request
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
    exit();
}


// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
require_once "../../config/Database.php";
// TODO: Create database connection
$database=new Database();
$db=$database->getConnection();
// TODO: Set PDO to throw exceptions on errors
   // already declared in the database file 

// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];
// TODO: Get the request body for POST and PUT requests
$raw_body=file_get_contents('php://input');
$data=json_decode($raw_body,true);
// TODO: Parse query parameters
$resource = isset($_GET['resource']) ? $_GET['resource'] : null;
// $id = isset($_GET['id']) ? $_GET['id'] : null;
// $assignmentId = isset($_GET['assignment_id']) ? $_GET['assignment_id'] : null;


// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    $search= $_GET['search'] ?? null;
    $sort= $_GET["sort"] ?? "due_date";
    $order= $_GET["order"] ?? "asc";

    // TODO: Start building the SQL query
    $sql = "SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments";
    // TODO: Check if 'search' query parameter exists in $_GET
    if (!empty($search)) {
        $sql.= " WHERE title LIKE ? OR description LIKE ?";
    }
    // TODO: Check if 'sort' and 'order' query parameters exist
    if(!validateAllowedValue($sort,["title", "due_date", "created_at"])){
        $sort="due_date";
    }
    if($order!=="asc" && $order!=="desc"){
        $order="asc";
    }    
    $sql.= " ORDER BY $sort $order";
    // TODO: Prepare the SQL statement using $db->prepare()
    $statement=$db->prepare($sql);
    // TODO: Bind parameters if search is used
    if(!empty($search)){
        $s="%$search%";
        $statement->bindValue(1,$s);
        $statement->bindValue(2,$s);
    }

    // TODO: Execute the prepared statement
    $statement->execute();
    // TODO: Fetch all results as associative array
    $result=$statement->fetchAll();
    // TODO: For each assignment, decode the 'files' field from JSON to array
    foreach($result as &$row){
        if(!empty($row["files"])){
            $row["files"]=json_decode($row["files"]);
        }
    }
    // TODO: Return JSON response
    sendResponse(["success" => true, "data" => $result],200);
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $id) {
    // TODO: Validate that $assignmentId is provided and not empty
     if(empty($id)){
        return sendError("id parameter is missing",400);
    }   
    // TODO: Prepare SQL query to select assignment by id
      $sql = "SELECT id, title, description, due_date, files, created_at, updated_at 
            FROM assignments 
            WHERE id = ?";
      $statement=$db->prepare($sql);
    // TODO: Bind the :id parameter
    $statement->bindValue(1,$id);
    // TODO: Execute the statement
    $statement->execute();
    // TODO: Fetch the result as associative array
    $result=$statement->fetch();
    // TODO: Check if assignment was found
    // TODO: Decode the 'files' field from JSON to array
    // TODO: Return success response with assignment data
 if($result){
        $result["files"]=json_decode($result["files"]);
        return sendResponse(["success" => true, "data" => $result]);
    }else{
        return sendError("Week not found",404);
    }
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
     if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        return sendError("Missing required fields",400);
    }
    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    
 if(!validateDate($data["due_date"])){
        return sendError("Invalid date format",400);
    }

    // TODO: Generate a unique assignment ID

    // TODO: Handle the 'files' field
if(isset($data["files"]) && is_array($data["files"])){
        $data["files"]=json_encode($data["files"]);
    }else{
        $data["files"]=json_encode([]);
    }
    // TODO: Prepare INSERT query
    $insert_sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";
    $statement=$db->prepare($insert_sql);
    // TODO: Bind all parameters
    $statement->bindParam(':title', $title);
    $statement->bindParam(':description', $description);
    $statement->bindParam(':due_date', $data["due_date"]);
    $statement->bindParam(':files', $data["files"]);
    // TODO: Execute the statement
    $result=$statement->execute();
    // TODO: Check if insert was successful
    // TODO: If insert failed, return 500 error
if($result){    
        return sendResponse(["success" => true, "data" => $db->lastInsertId()],201);
    }else{
        return sendError("Failed to create assignment",500);
    }

}

/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
    if(empty($data["id"])){
        return sendError("id parameter is missing",400);
    }
    // TODO: Store assignment ID in variable
    $assignmentId = $data['id'];
    // TODO: Check if assignment exists
 $sql="SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments WHERE id= ?";
    $statement=$db->prepare($sql);
    $statement->execute([$assignmentId]);
    $result=$statement->fetch();
    if(!$result){
        return sendError("Assignment not found",404);
    }
    // TODO: Build UPDATE query dynamically based on provided fields
    // TODO: Check which fields are provided and add to SET clause

    $clauses=[];
    $binding_values=[];
    $update_sql="UPDATE assignments SET ";

    if(isset($data["title"])){
        $clauses[]="title = ?";
        $binding_values[]=sanitizeInput($data["title"]);
    }

    if(isset($data["description"])){
        $clauses[]="description = ?";
        $binding_values[]=sanitizeInput($data["description"]);
    }

    if(isset($data["due_date"])){
    if(validateDate($data["due_date"])){
            $clauses[]="due_date = ?";
            $binding_values[]=$data["due_date"];
        }
    }

    if(isset($data["files"])){
        $data["files"]=json_encode($data["files"]);
        $clauses[]="files = ?";
        $binding_values[]=$data["files"];
    }

    
    // TODO: If no fields to update (besides updated_at), return 400 error
    if(empty($clauses)){
        return sendError("No fields to update",400);
    }
    $clauses[]="updated_at = CURRENT_TIMESTAMP";

    // TODO: Complete the UPDATE query
    foreach($clauses as $c){
        $update_sql.=$c .", ";
    }
    $update_sql = rtrim($update_sql, ", ");//removing last comma
    $update_sql.=" WHERE id = ?";

    // TODO: Prepare the statement
    $statement=$db->prepare($update_sql);
    // TODO: Bind all parameters dynamically
        $index=1;
  foreach($binding_values as $value){
        $statement->bindValue($index,$value);
        ++$index;
    }
    $statement->bindValue($index,$data["id"]);
    // TODO: Execute the statement
    $result=$statement->execute();
    // TODO: Check if update was successful
if($result){
        $statement=$db->prepare("SELECT id, title, description, due_date, files FROM assignments WHERE id = ?");
        $statement->bindValue(1, $data["id"]);
        $statement->execute();
        $assignment=$statement->fetch();
        $assignment["files"]=json_decode($assignment["files"]);

        return sendResponse(["success" => true, "data" => $assignment]);
    }else{
        return sendError("Failed to update assignment");
    }   
}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $id) {
    // TODO: Validate that $assignmentId is provided and not empty
 if(empty($id)){
        return sendError("id parameter is missing",400);
    }
    // TODO: Check if assignment exists
    $statement=$db->prepare("SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments WHERE id = ?");
    $statement->bindValue(1,$id);
    $statement->execute();
    $result=$statement->fetch();

    if(!$result){
        return sendError("Assignment not found",404);
    }
    // TODO: Delete associated comments first (due to foreign key constraint)
    $statement=$db->prepare("DELETE FROM comments_assignment WHERE assignment_id = ?");
    $statement->bindValue(1,$id);
    // TODO: Execute the statement
    $statement->execute();
    // TODO: Check if delete was successful
    $statement=$db->prepare("DELETE FROM assignments WHERE id = ?");
    $statement->bindValue(1,$id);
    $result=$statement->execute();
    if($result){
        return sendResponse(["success" => true, "message" => "Assignment and its comments were successfully deleted"]);
    }else{
        return sendError("Failed to delete assignment",500);
    }
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
     if (empty($assignmentId)) {
        return sendError('Assignment_id parameter is required', 400);
    }
    // TODO: Prepare SQL query to select all comments for the assignment
    $sql= "SELECT id, assignment_id, author, text, created_at 
                              FROM comments_assignment 
                              WHERE assignment_id = ?
                              ORDER BY created_at ASC";
    $statement=$db->prepare($sql);
    // TODO: Bind the :assignment_id parameter
    $statement->bindValue(1,$assignmentId);
    // TODO: Execute the statement
    $statement->execute();
    // TODO: Fetch all results as associative array
    $result=$statement->fetchAll();
    // TODO: Return success response with comments data
    return sendResponse(["success" => true, "data" => $result]);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
     if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        return sendError("Missing required fields",400);
    }
    // TODO: Sanitize input data
    $assignmentId=$data["assignment_id"];
    $author=sanitizeInput($data["author"]);
    $text=sanitizeInput($data["text"]);
    
    // TODO: Validate that text is not empty after trimming
    if(empty($author) || empty($text)){
        return sendError("Missing required fields",400);
    }

    // TODO: Verify that the assignment exists
    $statement=$db->prepare("SELECT * FROM assignments WHERE id = ?");
    $statement->bindValue(1,$assignmentId);
    $statement->execute();
    if(!$statement->fetch()){
        return sendError("Assignment not found",404);
    }


    // TODO: Prepare INSERT query for comment
    $insert_sql="INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ? , ?)";
    $statement=$db->prepare($insert_sql);

    // TODO: Bind all parameters
    $statement->bindValue(1,$assignmentId);
    $statement->bindValue(2,$author);
    $statement->bindValue(3,$text);

    // TODO: Execute the statement
    $result=$statement->execute();
    // TODO: Return success response with created comment data
    if($result){
        return sendResponse(["success" => true, "data" => $db->lastInsertId()],201);
    }else{
        return sendError("Failed to create comment",500);
    }

}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
     if (empty($commentId)) {
        return sendError("Missing comment_id",400);
    }
    // TODO: Check if comment exists
    $statement=$db->prepare("SELECT * FROM comments_assignment WHERE id = ?");
    $statement->bindValue(1,$commentId);
    $statement->execute();
    if(!$statement->fetch()){
        return sendError("Comment not found",404);
    }

    // TODO: Prepare DELETE query
    $statement=$db->prepare("DELETE FROM comments_assignment WHERE id = ?");
    // TODO: Bind the :id parameter
    $statement->bindValue(1,$commentId);
    // TODO: Execute the statement
    $result=$statement->execute();
    // TODO: Check if delete was successful
    // TODO: If delete failed, return 500 error
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
    // TODO: Get the 'resource' query parameter to determine which resource to access
    $resource=$resource??'assignments';

    // TODO: Route based on HTTP method and resource type
        if ($resource === 'assignments') {
            if ($method === 'GET') {
            // TODO: Check if 'id' query parameter exists
              if (!empty($_GET['id'])) {
                getAssignmentById($db,$_GET["id"]);
            } else {
                getAllAssignments($db);
            }
        } elseif ($method === 'POST') {
           createAssignment($db, $data);
        } elseif ($method === 'PUT') {
           updateAssignment($db, $data);       
        } elseif ($method === 'DELETE'){
           deleteAssignment($db,$_GET["id"]??($data["id"]??null));
        } else {
                sendError("Method Not Allowed",405);
        }
        
    } elseif ($resource === 'comments') {
        if ($method === 'GET') {
            // TODO: Get assignment_id from query parameters
            getCommentsByAssignment($db, $_GET["assignment_id"]);
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
        
    } else {
        // TODO: Invalid resource
     sendError("Invalid resource. Use 'weeks' or 'comments'",400);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    error_log($e->getMessage());
    sendError("Database error occurred",500);
} catch (Exception $e) {
    // TODO: Handle general errors
    error_log($e->getMessage());
    sendError("An unexpected error occurred",500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    // TODO: Ensure data is an array
    if(!is_array($data)){
        $data = ['message' => $data];
    }
    // TODO: Echo JSON encoded data
    // header('Content-Type: application/json'); check
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
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    $data = trim($data);
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);
    // TODO: Convert special characters to HTML entities
    $data = htmlspecialchars($data);
    // TODO: Return the sanitized data
    return $data;
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    $d=DateTime::createFromFormat('Y-m-d',$date);
    // TODO: Return true if valid, false otherwise
    return $d && $d->format('Y-m-d')==$date;

}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    return in_array($value,$allowedValues);    
}

?>
