<?php
use PHPUnit\Framework\TestCase;

// Include your main class file
require_once 'ClassroomJSONHandler.php';

class ClassroomJSONHandlerTest extends TestCase
{
    private $handler;
    private $testJsonFile = 'test_classroom_data.json';
    
    protected function setUp(): void
    {
        // Use a test file to avoid modifying your real data
        $this->handler = new ClassroomJSONHandler();
        
        // Override the JSON file path for testing
        $reflection = new ReflectionClass($this->handler);
        $property = $reflection->getProperty('jsonFile');
        $property->setAccessible(true);
        $property->setValue($this->handler, $this->testJsonFile);
        
        // Create fresh test data for each test
        $this->initializeTestData();
    }
    
    protected function tearDown(): void
    {
        // Clean up test file after each test
        if (file_exists($this->testJsonFile)) {
            unlink($this->testJsonFile);
        }
    }
    
    private function initializeTestData()
    {
        $testData = [
            'assignments' => [
                [
                    'id' => 1,
                    'title' => 'Test Assignment 1',
                    'description' => 'Test Description',
                    'due_date' => '2025-10-15',
                    'submissions' => [
                        [
                            'student_id' => 'student1',
                            'student_name' => 'Test Student 1',
                            'submitted_file' => 'test1.php',
                            'submission_date' => '2025-10-12 10:00:00',
                            'private_comments' => [
                                [
                                    'id' => 'comment1',
                                    'author' => 'teacher1',
                                    'author_name' => 'Test Teacher',
                                    'message' => 'Initial test comment',
                                    'timestamp' => '2025-10-12 11:00:00',
                                    'type' => 'teacher'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 2,
                    'title' => 'Test Assignment 2',
                    'description' => 'Another test',
                    'due_date' => '2025-10-20',
                    'submissions' => []
                ]
            ]
        ];
        
        file_put_contents($this->testJsonFile, json_encode($testData, JSON_PRETTY_PRINT));
    }
    
    // Test 1: Check if JSON file is created when it doesn't exist
    public function testJsonFileCreation()
    {
        $tempFile = 'temp_test.json';
        $handler = new ClassroomJSONHandler();
        
        $reflection = new ReflectionClass($handler);
        $property = $reflection->getProperty('jsonFile');
        $property->setAccessible(true);
        $property->setValue($handler, $tempFile);
        
        // Remove file if it exists
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        // Creating handler should create the file
        $handler = new ClassroomJSONHandler();
        $property->setValue($handler, $tempFile);
        
        $this->assertFileExists($tempFile);
        
        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
    
    // Test 2: Test reading all data from JSON file
    public function testGetAllData()
    {
        $result = $this->handler->getAllData();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('assignments', $result);
        $this->assertCount(2, $result['assignments']);
        $this->assertEquals('Test Assignment 1', $result['assignments'][0]['title']);
    }
    
    // Test 3: Test adding a private comment successfully
    public function testAddPrivateCommentSuccess()
    {
        $result = $this->handler->addPrivateComment(
            1, 
            'student1', 
            'teacher1', 
            'Test Teacher', 
            'This is a test comment', 
            'teacher'
        );
        
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Verify the comment was actually added
        $comments = $this->handler->getPrivateComments(1, 'student1');
        $this->assertCount(2, $comments);
        $this->assertEquals('This is a test comment', $comments[1]['message']);
    }
    
    // Test 4: Test adding comment to non-existent assignment
    public function testAddPrivateCommentAssignmentNotFound()
    {
        $result = $this->handler->addPrivateComment(
            999, // Non-existent assignment
            'student1', 
            'teacher1', 
            'Test Teacher', 
            'Test comment', 
            'teacher'
        );
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('not found', $result['error']);
    }
    
    // Test 5: Test adding comment to non-existent student
    public function testAddPrivateCommentStudentNotFound()
    {
        $result = $this->handler->addPrivateComment(
            1, 
            'nonexistent_student', 
            'teacher1', 
            'Test Teacher', 
            'Test comment', 
            'teacher'
        );
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('not found', $result['error']);
    }
    
    // Test 6: Test getting private comments for existing submission
    public function testGetPrivateCommentsSuccess()
    {
        $comments = $this->handler->getPrivateComments(1, 'student1');
        
        $this->assertIsArray($comments);
        $this->assertCount(1, $comments);
        $this->assertEquals('Initial test comment', $comments[0]['message']);
        $this->assertEquals('teacher1', $comments[0]['author']);
    }
    
    // Test 7: Test getting comments for non-existent submission
    public function testGetPrivateCommentsEmpty()
    {
        $comments = $this->handler->getPrivateComments(2, 'student1');
        
        $this->assertIsArray($comments);
        $this->assertEmpty($comments);
    }
    
    // Test 8: Test comment ID generation (should be unique)
    public function testCommentIdGeneration()
    {
        $result1 = $this->handler->addPrivateComment(
            1, 'student1', 'teacher1', 'Teacher', 'Comment 1', 'teacher'
        );
        
        $result2 = $this->handler->addPrivateComment(
            1, 'student1', 'student1', 'Student', 'Comment 2', 'student'
        );
        
        $comments = $this->handler->getPrivateComments(1, 'student1');
        
        // Should have 3 comments now (1 initial + 2 new)
        $this->assertCount(3, $comments);
        
        // All comment IDs should be unique
        $commentIds = array_column($comments, 'id');
        $uniqueIds = array_unique($commentIds);
        $this->assertCount(3, $uniqueIds);
    }
    
    // Test 9: Test timestamp format
    public function testCommentTimestamp()
    {
        $beforeAdd = time();
        $result = $this->handler->addPrivateComment(
            1, 'student1', 'teacher1', 'Teacher', 'Test timestamp', 'teacher'
        );
        $afterAdd = time();
        
        $comments = $this->handler->getPrivateComments(1, 'student1');
        $newComment = end($comments); // Get the last comment
        
        $commentTimestamp = strtotime($newComment['timestamp']);
        
        // Timestamp should be between before and after add operations
        $this->assertGreaterThanOrEqual($beforeAdd, $commentTimestamp);
        $this->assertLessThanOrEqual($afterAdd, $commentTimestamp);
    }
    
    // Test 10: Test data persistence
    public function testDataPersistence()
    {
        // Add a comment
        $this->handler->addPrivateComment(
            1, 'student1', 'teacher1', 'Teacher', 'Persistent comment', 'teacher'
        );
        
        // Create a new handler instance to simulate a new request
        $newHandler = new ClassroomJSONHandler();
        $reflection = new ReflectionClass($newHandler);
        $property = $reflection->getProperty('jsonFile');
        $property->setAccessible(true);
        $property->setValue($newHandler, $this->testJsonFile);
        
        // Check if data persisted
        $comments = $newHandler->getPrivateComments(1, 'student1');
        $this->assertCount(2, $comments);
        
        // Find our new comment
        $found = false;
        foreach ($comments as $comment) {
            if ($comment['message'] === 'Persistent comment') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Persistent comment should be found in new handler instance');
    }
    
    // Test 11: Test JSON file corruption handling
    public function testCorruptedJsonFile()
    {
        // Corrupt the JSON file
        file_put_contents($this->testJsonFile, '{ invalid json');
        
        $result = $this->handler->getAllData();
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Failed to decode JSON', $result['error']);
    }
    
    // Test 12: Test file permission issues
    public function testFilePermissionError()
    {
        $handler = new ClassroomJSONHandler();
        
        // Use a directory that should cause permission denied
        $reflection = new ReflectionClass($handler);
        $property = $reflection->getProperty('jsonFile');
        $property->setAccessible(true);
        $property->setValue($handler, '/root/forbidden_file.json');
        
        $result = $handler->getAllData();
        
        $this->assertArrayHasKey('error', $result);
    }
}
?>