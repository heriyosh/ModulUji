<?php
include("modulComment.php");

// Initialize the handler
$classroom = new ClassroomJSONHandler();

// Display private comments
function displayPrivateComments($assignmentId, $studentId) {
    global $classroom;
    $comments = $classroom->getPrivateComments($assignmentId, $studentId);
    
    if (empty($comments)) {
        echo "<p>No private comments yet.</p>";
        return;
    }
    
    echo '<div class="private-comments-container">';
    foreach ($comments as $comment) {
        $authorClass = ($comment['type'] == 'teacher') ? 'teacher-comment' : 'student-comment';
        echo "
        <div class='comment $authorClass'>
            <div class='comment-header'>
                <strong>{$comment['author_name']}</strong>
                <span class='timestamp'>{$comment['timestamp']}</span>
            </div>
            <div class='comment-body'>{$comment['message']}</div>
        </div>";
    }
    echo '</div>';
}

// Handle new comment submission
if(isset($_POST['action'])) {
    if ($_POST['action'] == 'add_private_comment') {
        $result = $classroom->addPrivateComment(
            $_POST['assignment_id'],
            $_POST['student_id'],
            $_POST['author'],
            $_POST['author_name'],
            $_POST['message'],
            $_POST['type']
        );
        
        if (isset($result['success'])) {
            echo "<div class='alert alert-success'>Comment added successfully!</div>";
        } else {
            echo "<div class='alert alert-error'>Error: {$result['error']}</div>";
        }
    }
}
?>

<!-- Private Comment Form -->
<div class="private-comment-form">
    <h4>Add Private Comment</h4>
    <form method="POST">
        <input type="hidden" name="action" value="add_private_comment">
        <input type="hidden" name="assignment_id" value="1">
        <input type="hidden" name="student_id" value="student1">
        <input type="hidden" name="author" value="teacher1">
        <input type="hidden" name="author_name" value="Mr. Smith">
        <input type="hidden" name="type" value="teacher">
        
        <div class="form-group">
            <textarea name="message" rows="4" placeholder="Enter your private feedback..." required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Post Private Comment</button>
    </form>
</div>

<!-- Display Existing Comments -->
<div class="private-comments-section">
    <h4>Private Feedback</h4>
    <?php displayPrivateComments(1, 'student1'); ?>
</div>