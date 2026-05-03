<?php
include("../config/db.php");
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!verify_csrf_token()) {
        $error = "Invalid request. Please try again.";
    } else {
        $title   = trim($_POST['title']);
        $content = trim($_POST['content']);
        $user_id = $_SESSION['user_id'];

        // Retrieve ML verification results from hidden fields
        $cred_label = $_POST['credibility_label'] ?? null;
        $cred_score = $_POST['credibility_score'] ?? null;

        // Validate label values
        $valid_labels = ['TRUE', 'FAKE', 'UNCERTAIN'];
        if ($cred_label && !in_array($cred_label, $valid_labels)) {
            $cred_label = null;
            $cred_score = null;
        }
        if ($cred_score !== null) {
            $cred_score = (float)$cred_score;
            if ($cred_score < 0 || $cred_score > 100) $cred_score = null;
        }

        if (empty($title) || empty($content)) {
            $error = "Title and content are required.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO blogs (user_id, title, content, credibility_label, credibility_score) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("isssd", $user_id, $title, $content, $cred_label, $cred_score);

            if ($stmt->execute()) {
                set_flash('success', 'Article published successfully!');
                header("Location: ../dashboard.php");
                exit;
            } else {
                $error = "Failed to publish. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Article — ThinkPost</title>
    <link rel="stylesheet" href="/ThinkPost/draw.css">
</head>

<body>
    <?php include("../includes/navbar.php"); ?>

    <div class="container">
        <h2>Write New Article</h2>

        <?php if (!empty($error)): ?>
            <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" id="blogForm">
            <?php echo csrf_input(); ?>

            <!-- Hidden fields to pass ML result to server -->
            <input type="hidden" name="credibility_label" id="credLabel" value="">
            <input type="hidden" name="credibility_score" id="credScore" value="">

            <label for="title">Title</label>
            <input type="text" id="title" name="title" required
                   placeholder="Enter an engaging title…"
                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">

            <label for="content">Content</label>
            <textarea name="content" id="content" rows="12" required
                      placeholder="Write your article here…"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>

            <button type="button" onclick="verifyArticle()" class="btn btn-secondary mb-2">
                🔍 Verify Article (ML)
            </button>

            <div id="verificationResult" class="verification-result" style="display:none;"></div>

            <button name="add" id="submitBtn" disabled>Publish Article</button>
        </form>
    </div>

    <script>
        function verifyArticle() {
            const text = document.getElementById("content").value.trim();
            if (!text) {
                alert("Please write the article content first.");
                return;
            }

            const resultDiv  = document.getElementById("verificationResult");
            const submitBtn  = document.getElementById("submitBtn");
            const credLabel  = document.getElementById("credLabel");
            const credScore  = document.getElementById("credScore");

            resultDiv.style.display = "block";
            resultDiv.className = "verification-result";
            resultDiv.textContent = "Analyzing article…";

            fetch("http://127.0.0.1:5000/predict", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "news=" + encodeURIComponent(text)
            })
            .then(res => {
                if (!res.ok) throw new Error("Server error");
                return res.json();
            })
            .then(data => {
                resultDiv.textContent = data.result + " — Confidence: " + data.confidence + "%";

                // Store ML result in hidden fields for DB storage
                if (data.result.includes("FAKE")) {
                    resultDiv.className = "verification-result text-red";
                    submitBtn.disabled = true;
                    credLabel.value = "FAKE";
                } else if (data.result.includes("UNCERTAIN")) {
                    resultDiv.className = "verification-result text-yellow";
                    submitBtn.disabled = false;
                    credLabel.value = "UNCERTAIN";
                } else {
                    resultDiv.className = "verification-result text-green";
                    submitBtn.disabled = false;
                    credLabel.value = "TRUE";
                }
                credScore.value = data.confidence;
            })
            .catch(() => {
                resultDiv.textContent = "Could not reach the verification server. You can still publish.";
                resultDiv.className = "verification-result text-yellow";
                submitBtn.disabled = false;
                credLabel.value = "";
                credScore.value = "";
            });
        }
    </script>
</body>

</html>
