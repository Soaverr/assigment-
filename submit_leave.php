<?php
if (isset($_POST['submit'])) {
    // ទទួលទិន្នន័យពី Form
    $name = $_POST['employee_name'];
    $type = $_POST['leave_type'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $reason = $_POST['reason'];

    // បង្ហាញលទ្ធផលសាកល្បង (អ្នកអាចសរសេរកូដ Insert ចូល MySQL នៅទីនេះ)
    echo "<div style='font-family: Kantumruy Pro; padding: 20px;'>";
    echo "<h2>ទទួលបានទិន្នន័យជោគជ័យ!</h2>";
    echo "<p>ឈ្មោះ៖ $name</p>";
    echo "<p>ប្រភេទច្បាប់៖ $type</p>";
    echo "<p>រយៈពេល៖ ពី $start ដល់ $end</p>";
    echo "<p>មូលហេតុ៖ $reason</p>";
    echo "<a href='index.php'>ត្រឡប់ក្រោយ</a>";
    echo "</div>";
}
