from flask import Flask, render_template, request, jsonify
from flask_cors import CORS
import joblib
import re
import os
import logging

import nltk
from nltk.corpus import stopwords
from nltk.stem import WordNetLemmatizer

# ── Flask App ────────────────────────────────────────────────────────
app = Flask(__name__)
CORS(app)
logging.basicConfig(level=logging.INFO)

# ── NLTK data (download only if missing) ─────────────────────────────
for resource in ['stopwords', 'wordnet', 'omw-1.4']:
    try:
        nltk.data.find(f'corpora/{resource}')
    except LookupError:
        nltk.download(resource, quiet=True)

# ── Load ML models ───────────────────────────────────────────────────
MODEL_DIR = os.path.join(os.path.dirname(__file__), "models")

try:
    model            = joblib.load(os.path.join(MODEL_DIR, "model.pkl"))
    count_vectorizer = joblib.load(os.path.join(MODEL_DIR, "count_vectorizer.pkl"))
    tfidf            = joblib.load(os.path.join(MODEL_DIR, "tfidf.pkl"))
    app.logger.info("ML models loaded successfully.")
except FileNotFoundError as e:
    app.logger.error(f"Model file not found: {e}")
    raise SystemExit("Cannot start without ML models.")

stop_words  = set(stopwords.words("english"))
lemmatizer  = WordNetLemmatizer()

# ── Text preprocessing ───────────────────────────────────────────────
def preprocess(text: str) -> str:
    text  = re.sub(r'[^\w\s]', '', text)
    words = text.lower().split()
    words = [lemmatizer.lemmatize(w) for w in words if w not in stop_words]
    return " ".join(words)

# ── Routes ────────────────────────────────────────────────────────────
@app.route("/")
def home():
    return render_template("index.html")

@app.route("/predict", methods=["POST"])
def predict():
    text = request.form.get("news", "").strip()

    if not text:
        return jsonify({"result": "Please enter some text", "confidence": 0}), 400

    if len(text) < 20:
        return jsonify({
            "result": "⚠️ UNCERTAIN",
            "confidence": 0,
            "true_probability": 0,
            "fake_probability": 0,
            "note": "Text too short for reliable classification."
        })

    try:
        cleaned    = preprocess(text)
        counts     = count_vectorizer.transform([cleaned])
        vectorized = tfidf.transform(counts)

        probabilities = model.predict_proba(vectorized)[0]
        fake_prob     = round(probabilities[0] * 100, 2)
        true_prob     = round(probabilities[1] * 100, 2)

        margin = abs(true_prob - fake_prob)

        if margin < 15:
            result = "⚠️ UNCERTAIN"
            confidence = max(true_prob, fake_prob)
        elif true_prob > fake_prob:
            result = "🟢 TRUE NEWS"
            confidence = true_prob
        else:
            result = "🔴 FAKE NEWS"
            confidence = fake_prob

        return jsonify({
            "result": result,
            "confidence": round(confidence, 2),
            "true_probability": true_prob,
            "fake_probability": fake_prob
        })

    except Exception as e:
        app.logger.error(f"Prediction error: {e}")
        return jsonify({"result": "Server error", "confidence": 0}), 500

# ── Health check ──────────────────────────────────────────────────────
@app.route("/health")
def health():
    return jsonify({"status": "ok"})

# ── Run ───────────────────────────────────────────────────────────────
if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)
