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

function onSignIn(googleUser) {
    var id_token = googleUser.getAuthResponse().id_token;
    // Send this token to your backend server via an AJAX request
    // For example, using fetch:
    fetch('google_auth.php', { // This backend script needs to be created
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'idtoken=' + id_token
    })
    .then(response => response.json()) // Assuming backend responds with JSON
    .then(data => {
        if (data.success) {
            // Redirect to dashboard or appropriate page
            window.location.href = data.redirect_url || 'dashboard.php';
        } else {
            // Display error message
            const messageArea = document.getElementById('message-area');
            if (messageArea) {
                messageArea.innerHTML = '<ul class="errors"><li>' + (data.message || 'Google Sign-In failed.') + '</li></ul>';
            } else {
                alert(data.message || 'Google Sign-In failed.');
            }
        }
    })
    .catch(error => {
        console.error('Error during Google Sign-In AJAX call:', error);
        const messageArea = document.getElementById('message-area');
        if (messageArea) {
            messageArea.innerHTML = '<ul class="errors"><li>Google Sign-In request failed. Check console.</li></ul>';
        } else {
             alert('Google Sign-In request failed. Check console.');
        }
    });
}

// Optional: Add a function to explicitly trigger sign-out if needed, though not strictly part of login
function signOutGoogle() {
  var auth2 = gapi.auth2.getAuthInstance();
  auth2.signOut().then(function () {
    console.log('User signed out of Google.');
    // You might want to also clear your own app's session here via a backend call
  });
}

document.addEventListener('DOMContentLoaded', function() {
    // ... (existing code for forms, onSignIn, etc.) ...

    const fadeInSections = document.querySelectorAll('.fade-in-section');
    const parallaxImages = document.querySelectorAll('.parallax-image');

    function checkVisibility() {
        fadeInSections.forEach(section => {
            const rect = section.getBoundingClientRect();
            // Check if section is partially in viewport
            if (rect.top < window.innerHeight && rect.bottom >= 0) {
                section.classList.add('is-visible');
            } else {
                // Optional: remove class if you want fade-out when scrolling up past it
                // section.classList.remove('is-visible');
            }
        });
    }

    function applyParallax() {
        const scrollY = window.scrollY;
        parallaxImages.forEach(image => {
            // Ensure the image's parent offset is considered if image is not directly in body
            const imageRect = image.getBoundingClientRect();
            const imageTopRelativeToDocument = scrollY + imageRect.top;
            
            // Calculate how much of the image is visible or its position relative to viewport center
            const scrollOffset = (imageTopRelativeToDocument - scrollY - (window.innerHeight / 2)) * 0.1; // Adjust 0.1 for parallax intensity

            // Apply a transform. Limit the transform to avoid excessive movement.
            // This is a simple parallax effect. More complex ones might consider the element's height.
            image.style.transform = `translateY(${scrollOffset}px)`;
        });
    }

    // Initial check in case elements are already visible on load
    checkVisibility();
    if (parallaxImages.length > 0) { // Only run parallax if elements exist
         applyParallax(); // Initial parallax position
    }


    window.addEventListener('scroll', function() {
        checkVisibility();
        if (parallaxImages.length > 0) {
             applyParallax();
        }
    });

}); // End of DOMContentLoaded
