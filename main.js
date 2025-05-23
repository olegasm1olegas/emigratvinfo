document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const loginForm = document.getElementById('loginForm');
    const messageArea = document.getElementById('message-area'); // Ensure this exists on both pages

    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            let errors = [];

            if (!username || username.length < 3 || username.length > 50) {
                errors.push("Username must be between 3 and 50 characters.");
            }
            if (!email || !/^\S+@\S+\.\S+$/.test(email)) { // Simple email regex
                errors.push("Valid email is required.");
            }
            if (!password || password.length < 8) {
                errors.push("Password must be at least 8 characters long.");
            }
            if (password !== passwordConfirm) {
                errors.push("Passwords do not match.");
            }

            if (errors.length > 0) {
                event.preventDefault(); // Stop form submission
                if (messageArea) { // Check if messageArea exists
                    messageArea.innerHTML = '<ul class="errors">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
                } else { // Fallback if messageArea is not found on the page for some reason
                     alert(errors.join('\n'));
                }
            }
            // If no client-side errors, form will submit naturally to PHP for server-side validation
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            const usernameOrEmail = document.getElementById('username_or_email').value.trim();
            const password = document.getElementById('password').value;
            let errors = [];

            if (!usernameOrEmail) {
                errors.push("Username or Email is required.");
            }
            if (!password) {
                errors.push("Password is required.");
            }

            if (errors.length > 0) {
                event.preventDefault(); // Stop form submission
                if (messageArea) { // Check if messageArea exists
                    messageArea.innerHTML = '<ul class="errors">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
                 } else { // Fallback
                     alert(errors.join('\n'));
                 }
            }
            // If no client-side errors, form submits
        });
    }
});
