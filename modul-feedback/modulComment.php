<?php
class ClassroomJSONHandler {
    private $jsonFile = 'db.json';
    
    public function __construct() {
        // Create file if it doesn't exist
        if (!file_exists($this->jsonFile)) {
            $initialData = ['assignments' => []];
            file_put_contents($this->jsonFile, json_encode($initialData, JSON_PRETTY_PRINT));
        }
    }
    
    // Read all data from JSON file:cite[3]:cite[8]
    public function getAllData() {
        $jsonData = file_get_contents($this->jsonFile);
        if ($jsonData === false) {
            return ['error' => 'Failed to read JSON file'];
        }
        
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Failed to decode JSON: ' . json_last_error_msg()];
        }
        
        return $data;
    }
    
    // Save all data to JSON file:cite[1]:cite[8]
    public function saveAllData($data) {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        if ($jsonData === false) {
            return ['error' => 'Failed to encode JSON data'];
        }
        
        $result = file_put_contents($this->jsonFile, $jsonData);
        if ($result === false) {
            return ['error' => 'Failed to write to JSON file'];
        }
        
        return ['success' => true];
    }
    
    // Add a private comment to a submission:cite[1]
    public function addPrivateComment($assignmentId, $studentId, $author, $authorName, $message, $type) {
        $data = $this->getAllData();
        if (isset($data['error'])) {
            return $data;
        }
        
        foreach ($data['assignments'] as &$assignment) {
            if ($assignment['id'] == $assignmentId) {
                foreach ($assignment['submissions'] as &$submission) {
                    if ($submission['student_id'] == $studentId) {
                        $newComment = [
                            'id' => time() . rand(100, 999),
                            'author' => $author,
                            'author_name' => $authorName,
                            'message' => $message,
                            'timestamp' => date('Y-m-d H:i:s'),
                            'type' => $type
                        ];
                        
                        $submission['private_comments'][] = $newComment;
                        return $this->saveAllData($data);
                    }
                }
            }
        }
        
        return ['error' => 'Assignment or student not found'];
    }
    
    // Get private comments for a specific submission:cite[3]
    public function getPrivateComments($assignmentId, $studentId) {
        $data = $this->getAllData();
        if (isset($data['error'])) {
            return $data;
        }
        
        foreach ($data['assignments'] as $assignment) {
            if ($assignment['id'] == $assignmentId) {
                foreach ($assignment['submissions'] as $submission) {
                    if ($submission['student_id'] == $studentId) {
                        return $submission['private_comments'] ?? [];
                    }
                }
            }
        }
        
        return [];
    }
}
?>