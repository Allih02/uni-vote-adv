<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'voting_system';
    private $username = 'root';  // Change to your MySQL username
    private $password = '';      // Change to your MySQL password
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// Helper functions for common database operations
function executeQuery($sql, $params = []) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

function insertRecord($table, $data) {
    $columns = implode(',', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    $stmt = executeQuery($sql, $data);
    
    if ($stmt) {
        $database = new Database();
        $db = $database->getConnection();
        return $db->lastInsertId();
    }
    return false;
}

function updateRecord($table, $data, $condition, $conditionParams = []) {
    $setClause = [];
    foreach (array_keys($data) as $key) {
        $setClause[] = "{$key} = :{$key}";
    }
    $setClause = implode(', ', $setClause);
    
    $sql = "UPDATE {$table} SET {$setClause} WHERE {$condition}";
    $params = array_merge($data, $conditionParams);
    
    return executeQuery($sql, $params);
}

function deleteRecord($table, $condition, $params = []) {
    $sql = "DELETE FROM {$table} WHERE {$condition}";
    return executeQuery($sql, $params);
}

// Voter-specific functions
function getVoters($filters = [], $limit = null, $offset = 0) {
    $sql = "SELECT v.*, 
                   COALESCE(vs.votes_cast, 0) as votes_cast,
                   COALESCE(vs.eligible_elections, 0) as eligible_elections
            FROM voters v
            LEFT JOIN voter_stats vs ON v.id = vs.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND v.status = :status";
        $params['status'] = $filters['status'];
    }
    
    if (!empty($filters['faculty'])) {
        $sql .= " AND v.faculty = :faculty";
        $params['faculty'] = $filters['faculty'];
    }
    
    if (!empty($filters['year'])) {
        $sql .= " AND v.year = :year";
        $params['year'] = $filters['year'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (v.full_name LIKE :search OR v.email LIKE :search OR v.student_id LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    $sql .= " ORDER BY v.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
    }
    
    return fetchAll($sql, $params);
}

function getVoterById($id) {
    $sql = "SELECT v.*, 
                   COALESCE(vs.votes_cast, 0) as votes_cast,
                   COALESCE(vs.eligible_elections, 0) as eligible_elections
            FROM voters v
            LEFT JOIN voter_stats vs ON v.id = vs.id
            WHERE v.id = :id";
    
    return fetchOne($sql, ['id' => $id]);
}

function getVoterByStudentId($student_id) {
    $sql = "SELECT * FROM voters WHERE student_id = :student_id";
    return fetchOne($sql, ['student_id' => $student_id]);
}

function getVoterStats() {
    $sql = "SELECT 
                COUNT(*) as total_voters,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_voters,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_voters,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_voters
            FROM voters";
    
    $stats = fetchOne($sql);
    
    // Get total votes cast
    $votesSql = "SELECT COUNT(*) as total_votes FROM votes";
    $votesStats = fetchOne($votesSql);
    
    // Get voters who voted today
    $todaySql = "SELECT COUNT(DISTINCT voter_id) as voted_today 
                 FROM votes 
                 WHERE DATE(voted_at) = CURDATE()";
    $todayStats = fetchOne($todaySql);
    
    return array_merge(
        $stats, 
        ['total_votes_cast' => $votesStats['total_votes']],
        ['voters_voted_today' => $todayStats['voted_today']]
    );
}

function createVoter($data) {
    // Hash the password (use student_id as default password)
    $data['password'] = password_hash($data['student_id'], PASSWORD_DEFAULT);
    
    return insertRecord('voters', $data);
}

function updateVoterStatus($voter_id, $status) {
    return updateRecord('voters', ['status' => $status], 'id = :id', ['id' => $voter_id]);
}

function deleteVoter($voter_id) {
    return deleteRecord('voters', 'id = :id', ['id' => $voter_id]);
}

function authenticateVoter($student_id, $password) {
    $voter = getVoterByStudentId($student_id);
    
    if ($voter && password_verify($password, $voter['password'])) {
        // Update last login
        updateRecord('voters', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $voter['id']]);
        return $voter;
    }
    
    return false;
}
?>