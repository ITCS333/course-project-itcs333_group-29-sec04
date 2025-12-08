<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once __DIR__ . '/../../config/Database.php';

// TODO: Get the PDO database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    sendResponse([
        'success' => false,
        'message' => 'Database connection failed.'
    ], 500);
}

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method    = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawInput  = file_get_contents('php://input');
$bodyData  = json_decode($rawInput, true);
if (!is_array($bodyData)) {
    $bodyData = [];
}

// TODO: Parse query parameters for filtering and searching
$queryParams = $_GET;
/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents(PDO $db) {
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields
    $search = isset($_GET['search'])? trim($_GET['search']): '';
    
    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
    $sort   = isset($_GET['sort']) ? trim($_GET['sort']) : 'name';
    $order  = isset($_GET['order']) ? strtolower(trim($_GET['order'])) : 'asc';
    $allowedSortFields = ['name', 'student_id', 'email'];
    if (!in_array($sort, $allowedSortFields, true)) {
        $sort = 'name';
    }
    switch ($sort) {
        case 'student_id':
            $sortColumn = 'id';
            break;
        case 'email':
            $sortColumn = 'email';
            break;
        case 'name':
        default:
            $sortColumn = 'name';
            break;
    }
    $order = ($order === 'desc') ? 'DESC' : 'ASC';

    $sql = "
        SELECT 
            id AS student_id,
            name,
            email,
            created_at
        FROM users
    ";
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE name LIKE :search OR email LIKE :search OR CAST(id AS CHAR) LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY {$sortColumn} {$order}";
    
    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    $stmt = $db->prepare($sql);
    // TODO: Bind parameters if using search
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch all results as an associative array
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // TODO: Return JSON response with success status and data
    sendResponse([
        'success' => true,
        'data'    => $rows
    ], 200);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById(PDO $db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    $studentId = trim($studentId);
    if ($studentId === '') {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required.'
        ], 400);
    }
    $sql = "
        SELECT 
            id AS student_id,
            name,
            email,
            created_at
        FROM users
        WHERE id = :student_id
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    // TODO: Bind the student_id parameter
    $stmt->bindValue(':student_id', (int) $studentId, PDO::PARAM_INT);
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch the result
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status
    if (!$row) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found.'
        ], 404);
    }

    sendResponse([
        'success' => true,
        'data'    => $row
    ], 200);
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent(PDO $db, array $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    $studentId = isset($data['student_id']) ? sanitizeInput($data['student_id']) : '';
    $name      = isset($data['name'])       ? sanitizeInput($data['name'])       : '';
    $email     = isset($data['email'])      ? sanitizeInput($data['email'])      : '';
    $password  = isset($data['password'])   ? $data['password']                  : '';
     if ($name === '' || $email === '' || $password === '') {
        sendResponse([
            'success' => false,
            'message' => 'name, email and password are required.'
        ], 400);
    }

    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()

   if (!validateEmail($email)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid email format.'
        ], 400);
    }
    if (strlen($password) < 8) {
        sendResponse([
            'success' => false,
            'message' => 'Password must be at least 8 characters.'
        ], 400);
    }
    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $checkSql = "SELECT COUNT(*) FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':email', $email, PDO::PARAM_STR);
    $checkStmt->execute();
    if ($checkStmt->fetchColumn() > 0) {
        sendResponse([
            'success' => false,
            'message' => 'Email already exists.'
        ], 409);
    }

    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
   $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    // TODO: Prepare INSERT query
   $insertSql = "
        INSERT INTO users (name, email, password, is_admin, created_at)
        VALUES (:name, :email, :password, 0, NOW())
    ";
    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
     $stmt = $db->prepare($insertSql);
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);

    // TODO: Execute the query
    $success = $stmt->execute();
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status
    if ($success) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Student created successfully.',
            'data'    => [
                'student_id' => $newId,
                'name'       => $name,
                'email'      => $email
            ]
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create student.'
        ], 500);
    }
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent(PDO $db, array $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    $studentId = isset($data['student_id']) ? trim($data['student_id']) : '';
    $name      = isset($data['name'])       ? sanitizeInput($data['name'])  : '';
    $email     = isset($data['email'])      ? sanitizeInput($data['email']) : '';

    if ($studentId === '') {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required.'
        ], 400);
    }
    if ($name === '' && $email === '') {
        sendResponse([
            'success' => false,
            'message' => 'Nothing to update.'
        ], 400);
    }

    if ($email !== '' && !validateEmail($email)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid email format.'
        ], 400);
    }


    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    $findSql = "SELECT id FROM users WHERE id = :id";
    $findStmt = $db->prepare($findSql);
    $findStmt->bindValue(':id', (int) $studentId, PDO::PARAM_INT);
    $findStmt->execute();
    if (!$findStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found.'
        ], 404);
    }
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $fields = [];
    $params = [':student_id' => $studentId];

    if ($name !== '') {
        $fields[] = 'name = :name';
        $params[':name'] = $name;
    }
    // TODO: If email is being updated, check if new email already exists
    // Prepare and execute a SELECT query
    // Exclude the current student from the check
    // If duplicate found, return error response with 409 status
    
if ($email !== '') {
    
        $checkSql = "SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindValue(':email', $email, PDO::PARAM_STR);
        $checkStmt->bindValue(':id', (int) $studentId, PDO::PARAM_INT);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() > 0) {
            sendResponse([
                'success' => false,
                'message' => 'Email already used by another user.'
            ], 409);
        }

        $fields[] = 'email = :email';
        $params[':email'] = $email;
    }

    // TODO: Bind parameters dynamically
    // Bind only the parameters that are being updated
    $updateSql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :student_id";
    $stmt = $db->prepare($updateSql);

    foreach ($params as $key => $value) {
        if ($key === ':student_id') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }

    // TODO: Execute the query
    $success = $stmt->execute();
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Student updated successfully.'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update student.'
        ], 500);
    }
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent(PDO $db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    $studentId = trim($studentId);

    if ($studentId === '') {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required.'
        ], 400);
    }
    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
   $sql = "DELETE FROM users WHERE id = :student_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':student_id', (int) $studentId, PDO::PARAM_INT);
    $stmt->execute();

     if ($stmt->rowCount() > 0) {
        sendResponse([
            'success' => true,
            'message' => 'Student deleted successfully.'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Student not found.'
        ], 404);
    }
    // TODO: Prepare DELETE query
    // TODO: Bind the student_id parameter
   
    // TODO: Execute the query
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status

}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword(PDO $db, array $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
    if (
        empty($data['student_id']) ||
        empty($data['current_password']) ||
        empty($data['new_password'])
    ) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.'
        ], 400);
    }
    $studentId       = trim($data['student_id']);
    $currentPassword = $data['current_password'];
    $newPassword     = $data['new_password'];

    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    if (strlen($newPassword) < 8) {
        sendResponse([
            'success' => false,
            'message' => 'New password must be at least 8 characters.'
        ], 400);
    }
    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    $sql = "SELECT id, password FROM users WHERE id = :student_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':student_id', (int) $studentId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found.'
        ], 404);
    }

    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
    if (!password_verify($currentPassword, $row['password'])) {
        sendResponse([
            'success' => false,
            'message' => 'Current password is incorrect.'
        ], 401);
    }
    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedNew = password_hash($newPassword, PASSWORD_DEFAULT);
    // TODO: Update password in database
    // Prepare UPDATE query
    $updateSql = "UPDATE users SET password = :password WHERE id = :student_id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->bindValue(':password', $hashedNew, PDO::PARAM_STR);
    $updateStmt->bindValue(':student_id', (int) $studentId, PDO::PARAM_INT);
    // TODO: Bind parameters and execute
    $success = $updateStmt->execute();
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Password updated successfully.'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update password.'
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

    // TODO: Route the request based on HTTP method
    
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
        
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
       
        // TODO: Call updateStudent()
       
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status

try {
    switch ($method) {
        case 'GET':
            if (isset($queryParams['student_id']) && $queryParams['student_id'] !== '') {
                $studentId = trim($queryParams['student_id']);
                getStudentById($db, $studentId);
            } else {
                getStudents($db);
            }
            break;

        case 'POST':
            if (isset($queryParams['action']) && $queryParams['action'] === 'change_password') {
                changePassword($db, $bodyData);
            } else {
                createStudent($db, $bodyData);
            }
            break;

        case 'PUT':
            updateStudent($db, $bodyData);
            break;

        case 'DELETE':
            if (!isset($queryParams['student_id']) || $queryParams['student_id'] === '') {
                sendResponse([
                    'success' => false,
                    'message' => 'student_id is required for DELETE.'
                ], 400);
            }
            $studentId = trim($queryParams['student_id']);
            deleteStudent($db, $studentId);
            break;

        default:
            sendResponse([
                'success' => false,
                'message' => 'Method not allowed.'
            ], 405);
    }
} catch (PDOException $e) {
    error_log('PDO Error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error.'
    ], 500);
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Server error.'
    ], 500);
}

// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, int $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    // TODO: Echo JSON encoded data
    echo json_encode($data);
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail(string $email): bool {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput(string $data): string {
    // TODO: Trim whitespace
    // TODO: Strip HTML tags using strip_tags()
    // TODO: Convert special characters using htmlspecialchars()
    // Return sanitized data
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $data;
}

?>
