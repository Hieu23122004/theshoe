document.addEventListener("DOMContentLoaded", function () {
    const codeInput = document.getElementById("verifyCode");

    codeInput.addEventListener("input", function () {
        if (codeInput.value.length === 6 && /^\d{6}$/.test(codeInput.value)) {
            const email = document.getElementById("forgotEmail").value;
            const code = codeInput.value.trim();

            clearInterval(emailCountdownInterval);

            document.getElementById("loadingTextEmail").style.display = "block";

            fetch('/public/forgot_pass_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    email,
                    code,
                    action: 'verify_code'
                })
            })
                .then(res => res.json())
                .then(data => {
                    document.getElementById("loadingTextEmail").style.display = "none";

                    if (data.status === "success") {
                        showForgotMessage(data.message, "success");
                        document.getElementById("codeSection").style.display = "none";
                        codeInput.value = "";
                    } else if (data.status === "expired") {
                        showForgotMessage("Verification code has expired. Please resend.", "error");
                        document.getElementById("resendEmailBtn").style.display = "block";
                    } else {
                        showForgotMessage(data.message || "Invalid code.", "error");
                        document.getElementById("resendEmailBtn").style.display = "block";
                    }
                })
                .catch(() => {
                    document.getElementById("loadingTextEmail").style.display = "none";
                    showForgotMessage("Connection error. Please try again.", "error");
                });
        }
    });
});

function sendCode() {
    const email = document.getElementById("forgotEmail").value;
    const loadingEl = document.getElementById("loadingTextEMAIL");

    if (!email) {
        showForgotMessage("Please enter your email.", "error");
        return;
    }

    // Hiện loading ngay lập tức
    loadingEl.style.display = "block";
    showForgotMessage("", ""); // clear message cũ

    fetch('/public/forgot_pass_login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            email,
            action: 'send_code'
        })
    })
        .then(res => res.json())
        .then(data => {
            loadingEl.style.display = "none";

            if (data.status === "success") {
                showForgotMessage("Verification code has been sent to your email!", "success");
                document.getElementById("codeSection").style.display = "block";
                document.getElementById("verifyCode").value = "";
                document.getElementById("verifyCode").focus();
                startEmailCountdown(120);
            } else {
                showForgotMessage(data.message, "error");
            }
        })
        .catch(() => {
            loadingEl.style.display = "none";
            showForgotMessage("An error occurred. Please try again.", "error");
        });
}

function resendCode() {
    sendCode();
}

function showLoading(show) {
    const loader = document.getElementById("loadingTextSMS");
    if (loader) {
        loader.style.display = show ? "block" : "none";
    }
}

function showForgotMessage(message, type = 'info') {
    const successEl = document.getElementById("forgotMessage");
    const errorEl = document.getElementById("forgotMessageloi");

    // Clear cả hai
    successEl.textContent = "";
    errorEl.textContent = "";

    if (type === "success") {
        successEl.textContent = message;
    } else {
        errorEl.textContent = message;
    }
}

let emailCountdownInterval;
let emailTimeLeft = 120;

function startEmailCountdown(seconds) {
    clearInterval(emailCountdownInterval);
    const countdownEl = document.getElementById("emailCountdown");
    const resendBtn = document.getElementById("resendEmailBtn");

    emailTimeLeft = seconds;
    resendBtn.style.display = "none";
    countdownEl.style.display = "block";
    updateEmailCountdownText(countdownEl);

    emailCountdownInterval = setInterval(() => {
        emailTimeLeft--;
        if (emailTimeLeft <= 0) {
            clearInterval(emailCountdownInterval);
            countdownEl.innerText = "Verification code has expired.";
            resendBtn.style.display = "block";
        } else {
            updateEmailCountdownText(countdownEl);
        }
    }, 1000);
}

function updateEmailCountdownText(el) {
    el.innerText = `Verification code is valid for ${emailTimeLeft} seconds`;
}