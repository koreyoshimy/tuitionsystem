<?php
session_start();
include("db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$child = $_POST['child'];
$month = $_POST['month'];
$year = $_POST['year'];

// Get subjects with fees for the selected month/year
$query = "SELECT sf.subject_id, sf.subject_name, 
          COALESCE(mf.fee_amount, sf.fee_amount) as fee_amount
          FROM student_subjects ss
          JOIN subject_fees sf ON ss.subject_id = sf.subject_id
          LEFT JOIN monthly_fees mf ON sf.subject_id = mf.subject_id
              AND mf.month = ? AND mf.year = ?
          WHERE ss.student_username = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $month, $year, $child);
$stmt->execute();
$subjects = $stmt->get_result();

while ($subject = $subjects->fetch_assoc()): ?>
<div class="subject-card">
    <div class="form-check">
        <input class="form-check-input subject-check" type="checkbox" 
               name="subjects[]" value="<?= $subject['subject_id'] ?>"
               id="subj<?= $subject['subject_id'] ?>" checked
               data-fee="<?= $subject['fee_amount'] ?>">
        <label class="form-check-label w-100" for="subj<?= $subject['subject_id'] ?>">
            <div class="d-flex justify-content-between">
                <span><?= $subject['subject_name'] ?></span>
                <span class="text-muted">RM <?= number_format($subject['fee_amount'], 2) ?></span>
            </div>
        </label>
    </div>
</div>
<?php endwhile; ?>