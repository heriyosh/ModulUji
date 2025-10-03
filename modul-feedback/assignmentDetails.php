<?php
require_once 'gradingModule.php';
$gradingModule = new GradingModule();

$assignmentId = $_GET['id'] ?? null;
if (!$assignmentId) die('Assignment ID required');

$submissions = $gradingModule->getAssignmentSubmissions($assignmentId);
if (isset($submissions['error'])) die($submissions['error']);
?>

<div class="card">
    <h2>Grading Interface: <?php echo htmlspecialchars($submissions['assignment']['title']); ?></h2>
    
    <div class="submission-list">
        <?php foreach ($submissions['submissions'] as $submission): ?>
        <div class="submission-item">
            <div class="submission-header">
                <h4><?php echo htmlspecialchars($submission['student_name']); ?></h4>
                <span class="status-<?php echo $submission['status']; ?>">
                    Status: <?php echo $gradingModule->getStatusOptions()[$submission['status']]; ?>
                </span>
            </div>
            
            <p><strong>Submitted:</strong> <?php echo $submission['submission_date']; ?></p>
            <p><strong>File:</strong> <?php echo htmlspecialchars($submission['submitted_file']); ?></p>
            
            <?php if ($submission['grade']): ?>
            <div class="grade-display">
                <h4>Grade: <?php echo $submission['grade']['points_earned']; ?>/<?php echo $submissions['assignment']['max_points']; ?> 
                    (<?php echo $submission['grade']['percentage']; ?>%)</h4>
                <p><strong>Feedback:</strong> <?php echo htmlspecialchars($submission['grade']['feedback']); ?></p>
                <p><strong>Graded by:</strong> <?php echo $submission['grade']['graded_by']; ?> on <?php echo $submission['grade']['graded_date']; ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Grading Form -->
            <form method="POST" style="margin-top: 15px;">
                <input type="hidden" name="action" value="grade_assignment">
                <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                <input type="hidden" name="student_id" value="<?php echo $submission['student_id']; ?>">
                <input type="hidden" name="graded_by" value="teacher1">
                
                <div class="form-group">
                    <label>Points Earned:</label>
                    <input type="number" name="points_earned" 
                           max="<?php echo $submissions['assignment']['max_points']; ?>" 
                           value="<?php echo $submission['grade']['points_earned'] ?? ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label>Feedback:</label>
                    <textarea name="feedback" rows="3" required><?php echo $submission['grade']['feedback'] ?? ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo $submission['grade'] ? 'Update Grade' : 'Submit Grade'; ?>
                </button>
            </form>
            
            <!-- Status Update -->
            <form method="POST" style="margin-top: 10px;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                <input type="hidden" name="student_id" value="<?php echo $submission['student_id']; ?>">
                
                <select name="status" onchange="this.form.submit()">
                    <?php foreach ($gradingModule->getStatusOptions() as $status => $label): ?>
                    <option value="<?php echo $status; ?>" <?php echo $submission['status'] == $status ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>