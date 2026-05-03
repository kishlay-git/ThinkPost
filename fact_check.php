<?php
include("config/db.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fact Check — ThinkPost</title>
    <link rel="stylesheet" href="/ThinkPost/draw.css">
</head>

<body>
    <?php include("includes/navbar.php"); ?>

    <div class="container">
        <h2>Fact Check</h2>
        <p class="text-center" style="margin-top: -16px; margin-bottom: 32px; color: var(--text-secondary);">
            Paste any news article below and our ML model will analyze its credibility.
        </p>

        <div class="fact-check-card">
            <label for="newsText">Article Text</label>
            <textarea id="newsText" rows="10"
                      placeholder="Paste the news article text here…"
                      class="fact-check-textarea"></textarea>

            <button type="button" onclick="checkArticle()" id="checkBtn" class="btn btn-create" style="width: 100%; justify-content: center;">
                🔍 Analyze Article
            </button>

            <!-- Results Panel (hidden initially) -->
            <div id="resultPanel" class="fact-check-result" style="display: none;">
                <div id="resultLabel" class="result-label"></div>
                <div class="result-meters">
                    <div class="meter-group">
                        <span class="meter-label">True Probability</span>
                        <div class="meter-bar">
                            <div id="trueBar" class="meter-fill meter-true" style="width: 0%"></div>
                        </div>
                        <span id="truePercent" class="meter-value">0%</span>
                    </div>
                    <div class="meter-group">
                        <span class="meter-label">Fake Probability</span>
                        <div class="meter-bar">
                            <div id="fakeBar" class="meter-fill meter-fake" style="width: 0%"></div>
                        </div>
                        <span id="fakePercent" class="meter-value">0%</span>
                    </div>
                </div>
                <p id="resultNote" class="result-note"></p>
            </div>
        </div>

        <!-- How It Works -->
        <div class="how-it-works">
            <h3>How It Works</h3>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <strong>Text Preprocessing</strong>
                        <p>Your text is cleaned, tokenized, and lemmatized using NLP techniques.</p>
                    </div>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-text">
                        <strong>TF-IDF Vectorization</strong>
                        <p>Words are converted into numerical features weighted by importance.</p>
                    </div>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-text">
                        <strong>ML Classification</strong>
                        <p>A Logistic Regression model trained on 44,000+ articles makes the prediction.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkArticle() {
            const text = document.getElementById("newsText").value.trim();
            if (!text) {
                alert("Please paste an article first.");
                return;
            }

            const btn = document.getElementById("checkBtn");
            const panel = document.getElementById("resultPanel");
            btn.textContent = "Analyzing…";
            btn.disabled = true;

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
                panel.style.display = "block";

                const labelDiv = document.getElementById("resultLabel");
                labelDiv.textContent = data.result + " — Confidence: " + data.confidence + "%";

                if (data.result.includes("TRUE")) {
                    labelDiv.className = "result-label result-true";
                } else if (data.result.includes("FAKE")) {
                    labelDiv.className = "result-label result-fake";
                } else {
                    labelDiv.className = "result-label result-uncertain";
                }

                // Animate probability bars
                const trueP = data.true_probability || 0;
                const fakeP = data.fake_probability || 0;

                document.getElementById("trueBar").style.width = trueP + "%";
                document.getElementById("truePercent").textContent = trueP + "%";
                document.getElementById("fakeBar").style.width = fakeP + "%";
                document.getElementById("fakePercent").textContent = fakeP + "%";

                const note = document.getElementById("resultNote");
                if (data.note) {
                    note.textContent = data.note;
                    note.style.display = "block";
                } else {
                    note.textContent = "This is an automated prediction. Always verify news through multiple trusted sources.";
                    note.style.display = "block";
                }

                btn.textContent = "🔍 Analyze Again";
                btn.disabled = false;
            })
            .catch(() => {
                panel.style.display = "block";
                document.getElementById("resultLabel").textContent = "Could not reach the ML server.";
                document.getElementById("resultLabel").className = "result-label result-uncertain";
                document.getElementById("resultNote").textContent = "Make sure the Flask server is running on port 5000.";
                document.getElementById("resultNote").style.display = "block";
                btn.textContent = "🔍 Analyze Article";
                btn.disabled = false;
            });
        }
    </script>
</body>

</html>
