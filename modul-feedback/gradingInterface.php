<?php
// Include the grading module
require_once 'gradingModule.php';
$gradingModule = new GradingModule();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_assignment':
                $result = $gradingModule->createAssignment(
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['due_date'],
                    $_POST['max_points'],
                    $_POST['grading_criteria'] ?? []
                );
                break;
                
            case 'grade_assignment':
                $result = $gradingModule->gradeAssignment(
                    $_POST['assignment_id'],
                    $_POST['student_id'],
                    $_POST['points_earned'],
                    $_POST['breakdown'],
                    $_POST['feedback'],
                    $_POST['graded_by']
                );
                break;
                
            case 'update_status':
                $result = $gradingModule->updateSubmissionStatus(
                    $_POST['assignment_id'],
                    $_POST['student_id'],
                    $_POST['status']
                );
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grading Module - Classroom Replica</title>
    <style>
        .grading-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #4caf50; color: white; }
        .status-missing { color: #f44336; }
        .status-submitted { color: #ff9800; }
        .status-graded { color: #2196f3; }
        .status-returned { color: #4caf50; }
        .status-late { color: #ff5722; }
        .grade-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .submission-list { max-height: 400px; overflow-y: auto; }
        .submission-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="grading-container">
        <h1>Classroom Grading Module</h1>
        
        <!-- Create Assignment Form -->
        <div class="card">
            <h2>Create New Assignment</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_assignment">
                <div class="form-group">
                    <label>Assignment Title:</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Due Date:</label>
                    <input type="datetime-local" name="due_date" required>
                </div>
                <div class="form-group">
                    <label>Maximum Points:</label>
                    <input type="number" name="max_points" value="100" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Assignment</button>
            </form>
        </div>

        <!-- Assignment List -->
        <div class="card">
            <h2>Assignment Submissions</h2>
            <div class="grade-grid">
                <?php
                $data = $gradingModule->loadData();
                foreach ($data['assignments'] as $assignment): 
                    $stats = $gradingModule->getAssignmentStatistics($assignment['id']);
                ?>
                <div class="submission-item">
                    <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                    <p><strong>Due:</strong> <?php echo $assignment['due_date']; ?></p>
                    <p><strong>Max Points:</strong> <?php echo $assignment['max_points']; ?></p>
                    
                    <?php if (!isset($stats['error'])): ?>
                    <div class="assignment-stats">
                        <p>Submitted: <?php echo $stats['submitted_count']; ?>/<?php echo $stats['total_students']; ?></p>
                        <p>Graded: <?php echo $stats['graded_count']; ?></p>
                        <p>Missing: <?php echo $stats['missing_count']; ?></p>
                        <?php if ($stats['graded_count'] > 0): ?>
                            <p>Average: <?php echo $stats['average_grade']; ?>%</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <a href="assignment_detail.php?id=<?php echo $assignment['id']; ?>" class="btn">
                        View Submissions
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Status Legend -->
        <div class="card">
            <h3>Status Legend</h3>
            <div class="status-legend">
                <?php foreach ($gradingModule->getStatusOptions() as $status => $label): ?>
                <span class="status-<?php echo $status; ?>">● <?php echo $label; ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>