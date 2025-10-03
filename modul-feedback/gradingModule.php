<?php
class GradingModule {
    private $jsonFile = 'db.json';
    
    public function __construct($jsonFile = null) {
        if ($jsonFile) {
            $this->jsonFile = $jsonFile;
        }
    }
    
    // Assignment status constants
    const STATUS_MISSING = 'missing';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_GRADED = 'graded';
    const STATUS_RETURNED = 'returned';
    const STATUS_LATE = 'late';
    
    // Get all possible statuses
    public function getStatusOptions() {
        return [
            self::STATUS_MISSING => 'Missing',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_GRADED => 'Graded',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_LATE => 'Late'
        ];
    }
    
    // Create a new assignment with grading criteria
    public function createAssignment($title, $description, $dueDate, $maxPoints = 100, $gradingCriteria = []) {
        $data = $this->loadData();
        
        $newAssignment = [
            'id' => $this->generateAssignmentId($data),
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate,
            'max_points' => $maxPoints,
            'grading_criteria' => $gradingCriteria ?: ['overall' => 100],
            'submissions' => [],
            'created_date' => date('Y-m-d H:i:s')
        ];
        
        $data['assignments'][] = $newAssignment;
        return $this->saveData($data);
    }
    
    // Submit an assignment
    public function submitAssignment($assignmentId, $studentId, $studentName, $submittedFile) {
        $data = $this->loadData();
        
        foreach ($data['assignments'] as &$assignment) {
            if ($assignment['id'] == $assignmentId) {
                $submission = [
                    'student_id' => $studentId,
                    'student_name' => $studentName,
                    'submitted_file' => $submittedFile,
                    'submission_date' => date('Y-m-d H:i:s'),
                    'status' => $this->checkIfLate($assignment['due_date']) ? self::STATUS_LATE : self::STATUS_SUBMITTED,
                    'grade' => null
                ];
                
                // Remove existing submission if any
                $assignment['submissions'] = array_filter($assignment['submissions'], 
                    function($sub) use ($studentId) { return $sub['student_id'] != $studentId; });
                
                $assignment['submissions'][] = $submission;
                return $this->saveData($data);
            }
        }
        
        return ['error' => 'Assignment not found'];
    }
    
    // Grade an assignment
    public function gradeAssignment($assignmentId, $studentId, $pointsEarned, $breakdown, $feedback, $gradedBy) {
        $data = $this->loadData();
        
        foreach ($data['assignments'] as &$assignment) {
            if ($assignment['id'] == $assignmentId) {
                foreach ($assignment['submissions'] as &$submission) {
                    if ($submission['student_id'] == $studentId) {
                        $percentage = round(($pointsEarned / $assignment['max_points']) * 100, 2);
                        
                        $submission['grade'] = [
                            'points_earned' => $pointsEarned,
                            'percentage' => $percentage,
                            'breakdown' => $breakdown,
                            'feedback' => $feedback,
                            'graded_by' => $gradedBy,
                            'graded_date' => date('Y-m-d H:i:s')
                        ];
                        
                        $submission['status'] = self::STATUS_GRADED;
                        
                        return $this->saveData($data);
                    }
                }
                return ['error' => 'Student submission not found'];
            }
        }
        
        return ['error' => 'Assignment not found'];
    }
    
    // Return assignment to student (make grade visible)
    public function returnAssignment($assignmentId, $studentId) {
        return $this->updateSubmissionStatus($assignmentId, $studentId, self::STATUS_RETURNED);
    }
    
    // Mark assignment as missing
    public function markAsMissing($assignmentId, $studentId) {
        return $this->updateSubmissionStatus($assignmentId, $studentId, self::STATUS_MISSING);
    }
    
    // Get student grade for an assignment
    public function getStudentGrade($assignmentId, $studentId) {
        $data = $this->loadData();
        
        foreach ($data['assignments'] as $assignment) {
            if ($assignment['id'] == $assignmentId) {
                foreach ($assignment['submissions'] as $submission) {
                    if ($submission['student_id'] == $studentId) {
                        return [
                            'submission' => $submission,
                            'assignment' => [
                                'title' => $assignment['title'],
                                'max_points' => $assignment['max_points']
                            ]
                        ];
                    }
                }
            }
        }
        
        return ['error' => 'No submission found'];
    }
    
    // Get all submissions for an assignment with filtering
    public function getAssignmentSubmissions($assignmentId, $statusFilter = null) {
        $data = $this->loadData();
        
        foreach ($data['assignments'] as $assignment) {
            if ($assignment['id'] == $assignmentId) {
                $submissions = $assignment['submissions'];
                
                if ($statusFilter) {
                    $submissions = array_filter($submissions, 
                        function($sub) use ($statusFilter) { return $sub['status'] == $statusFilter; });
                }
                
                return [
                    'assignment' => $assignment,
                    'submissions' => $submissions,
                    'summary' => $this->calculateAssignmentSummary($assignment['submissions'])
                ];
            }
        }
        
        return ['error' => 'Assignment not found'];
    }
    
    // Calculate class statistics for an assignment
    public function getAssignmentStatistics($assignmentId) {
        $result = $this->getAssignmentSubmissions($assignmentId);
        if (isset($result['error'])) return $result;
        
        $submissions = $result['submissions'];
        $gradedSubmissions = array_filter($submissions, function($sub) { 
            return !empty($sub['grade']); 
        });
        
        if (empty($gradedSubmissions)) {
            return [
                'total_students' => count($submissions),
                'graded_count' => 0,
                'average_grade' => 0,
                'grade_distribution' => []
            ];
        }
        
        $grades = array_column($gradedSubmissions, 'grade');
        $percentages = array_column($grades, 'percentage');
        
        return [
            'total_students' => count($submissions),
            'submitted_count' => count(array_filter($submissions, function($sub) {
                return in_array($sub['status'], [self::STATUS_SUBMITTED, self::STATUS_GRADED, self::STATUS_RETURNED, self::STATUS_LATE]);
            })),
            'graded_count' => count($gradedSubmissions),
            'missing_count' => count(array_filter($submissions, function($sub) {
                return $sub['status'] == self::STATUS_MISSING;
            })),
            'average_grade' => round(array_sum($percentages) / count($percentages), 2),
            'grade_distribution' => $this->calculateGradeDistribution($percentages)
        ];
    }
    
    // Private helper methods
    public function loadData() {
        $jsonData = file_get_contents($this->jsonFile);
        return json_decode($jsonData, true) ?: ['assignments' => []];
    }
    
    private function saveData($data) {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        return file_put_contents($this->jsonFile, $jsonData) !== false 
            ? ['success' => true] 
            : ['error' => 'Failed to save data'];
    }
    
    private function generateAssignmentId($data) {
        $maxId = 0;
        foreach ($data['assignments'] as $assignment) {
            $maxId = max($maxId, $assignment['id']);
        }
        return $maxId + 1;
    }
    
    private function checkIfLate($dueDate) {
        return strtotime(date('Y-m-d H:i:s')) > strtotime($dueDate);
    }
    
    public function updateSubmissionStatus($assignmentId, $studentId, $status) {
        $data = $this->loadData();
        
        foreach ($data['assignments'] as &$assignment) {
            if ($assignment['id'] == $assignmentId) {
                foreach ($assignment['submissions'] as &$submission) {
                    if ($submission['student_id'] == $studentId) {
                        $submission['status'] = $status;
                        return $this->saveData($data);
                    }
                }
            }
        }
        
        return ['error' => 'Submission not found'];
    }
    
    private function calculateAssignmentSummary($submissions) {
        $statusCounts = array_count_values(array_column($submissions, 'status'));
        $statusOptions = $this->getStatusOptions();
        
        $summary = [];
        foreach ($statusOptions as $status => $label) {
            $summary[$status] = $statusCounts[$status] ?? 0;
        }
        
        return $summary;
    }
    
    private function calculateGradeDistribution($percentages) {
        $ranges = [
            'A (90-100)' => 0,
            'B (80-89)' => 0,
            'C (70-79)' => 0,
            'D (60-69)' => 0,
            'F (0-59)' => 0
        ];
        
        foreach ($percentages as $percentage) {
            if ($percentage >= 90) $ranges['A (90-100)']++;
            elseif ($percentage >= 80) $ranges['B (80-89)']++;
            elseif ($percentage >= 70) $ranges['C (70-79)']++;
            elseif ($percentage >= 60) $ranges['D (60-69)']++;
            else $ranges['F (0-59)']++;
        }
        
        return $ranges;
    }
}
?>