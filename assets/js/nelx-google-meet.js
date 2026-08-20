jQuery(document).ready(function($) {
    // Auto-save email when user clicks outside the input field
    $('#nelx_google_meet_email').on('blur', function() {
        var email = $(this).val().trim();
        
        if (!email) {
            return;
        }
        
        if (!isValidEmail(email)) {
            showMessage('Please enter a valid email address', 'error');
            return;
        }
        
        saveEmail(email);
    });
    
    // Also save on Enter key press
    $('#nelx_google_meet_email').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            var email = $(this).val().trim();
            
            if (!email || !isValidEmail(email)) {
                showMessage('Please enter a valid email address', 'error');
                return;
            }
            
            saveEmail(email);
        }
    });
    
    // Handle Google Connect button click
    $(document).on('click', '#nelx_connect_google', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        
        if (url === '#') {
            showMessage('Google integration is not properly configured. Please contact administrator.', 'error');
            return;
        }
        
        // Open in new window for OAuth flow
        var authWindow = window.open(url, 'google_auth', 'width=600,height=700,scrollbars=yes');
        
        // Check if the window was blocked
        if (!authWindow || authWindow.closed || typeof authWindow.closed === 'undefined') {
            showMessage('Popup window was blocked. Please allow popups for this site and try again.', 'error');
        } else {
            showMessage('Opening Google authentication window...', 'info');
        }
    });
    
    // Disconnect Google account
    $(document).on('click', '#nelx_disconnect_google', function() {
        if (!confirm('Are you sure you want to disconnect your Google account? This will prevent Google Meet creation for your appointments.')) {
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('Disconnecting...');
        
        $.ajax({
            url: nelxGoogleMeet.ajax_url,
            type: 'POST',
            data: {
                action: 'disconnect_google',
                nonce: nelxGoogleMeet.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(response.data.message, 'error');
                    button.prop('disabled', false).text('Disconnect Google');
                }
            },
            error: function(xhr, status, error) {
                showMessage('An error occurred while disconnecting. Please try again.', 'error');
                button.prop('disabled', false).text('Disconnect Google');
            }
        });
    });
    
    // Save email function
    function saveEmail(email) {
        // Show saving indicator
        var inputField = $('#nelx_google_meet_email');
        var originalBorder = inputField.css('border-color');
        inputField.css('border-color', '#0073aa').css('background-color', '#f8f9fa');
        
        $.ajax({
            url: nelxGoogleMeet.ajax_url,
            type: 'POST',
            data: {
                action: 'save_google_meet_email',
                email: email,
                nonce: nelxGoogleMeet.nonce
            },
            beforeSend: function() {
                showMessage('Saving email...', 'info');
            },
            success: function(response) {
                if (response.success) {
                    var fullMessage = response.data.message;
                    if (response.data.description) {
                        fullMessage += '<br><small>' + response.data.description + '</small>';
                    }
                    showMessage(fullMessage, 'success');
                    inputField.css('border-color', '#28a745').css('background-color', '#f8fff9');
                    
                    // Reset border color after delay
                    setTimeout(function() {
                        inputField.css('border-color', originalBorder).css('background-color', '');
                    }, 2000);
                } else {
                    showMessage(response.data.message, 'error');
                    inputField.css('border-color', '#dc3545').css('background-color', '#fff5f5');
                }
            },
            error: function(xhr, status, error) {
                showMessage('An error occurred while saving. Please try again.', 'error');
                inputField.css('border-color', originalBorder).css('background-color', '');
            }
        });
    }
    
    // Email validation
    function isValidEmail(email) {
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    // Show message
    function showMessage(message, type) {
        var messageDiv = $('.nelx-message');
        if (messageDiv.length === 0) {
            // Create message div if it doesn't exist
            $('.nelx-google-meet-settings').append('<div class="nelx-message" style="display: none;"></div>');
            messageDiv = $('.nelx-message').last();
        }
        
        messageDiv.removeClass('success error info').addClass(type).html(message).stop().slideDown(200);
        
        // Only auto-hide success and info messages
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                messageDiv.slideUp(200);
            }, type === 'success' ? 5000 : 3000);
        }
    }
    
    // Check for URL parameters to show messages (like after OAuth redirect)
    var urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('google_auth')) {
        if (urlParams.get('google_auth') === 'success') {
            showMessage('✅ Successfully connected to Google! Your account is now ready for Google Meet integration.', 'success');
            
            // Clean URL without page reload
            var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    }
    
    // Handle OAuth callback from popup window (if any)
    if (typeof window.opener !== 'undefined' && window.opener !== null) {
        try {
            // Check if this is an OAuth callback
            if (window.location.search.includes('code=') || window.location.search.includes('error=')) {
                window.opener.postMessage({
                    type: 'google_oauth_callback',
                    url: window.location.href
                }, '*');
                window.close();
            }
        } catch (e) {
            // Silently fail
        }
    }
    
    // Listen for messages from popup windows
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'google_oauth_callback') {
            // Handle the callback if needed
        }
    });
});