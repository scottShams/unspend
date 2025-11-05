// Common JavaScript functions shared between pages

// Modal Control Functions
window.openModal = function(id) {
    // Check analysis limit before opening upload modal
    if (id === 'uploadModal' || id === 'contactModal') {
        fetch('get_session.php')
            .then(response => response.json())
            .then(data => {
                // Check if user has reached the free limit (3 analyses) and has no additional credits
                if (data.analysis_count == 3) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Analysis Limit Reached',
                        text: 'You have already analyzed 3 PDFs. Please upgrade to continue.',
                        confirmButtonText: 'Upgrade Now'
                    }).then(() => {
                        window.location.href = 'pricing.php';
                    });
                    return;
                }

                // Check if user has additional credits but has used them all
                if (data.remaining_credits <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Credits Exhausted',
                        text: 'You have used all your purchased credits. Upgrade to continue analyzing PDFs.',
                        confirmButtonText: 'Upgrade Now'
                    }).then(() => {
                        window.location.href = 'pricing.php';
                    });
                    return;
                }
                // Proceed to open modal
                document.getElementById(id).classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent scrolling background
            })
            .catch(error => {
                console.error('Error checking analysis count:', error);
                // Proceed to open modal on error
                document.getElementById(id).classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });
    } else {
        document.getElementById(id).classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling background
    }
};

window.closeModal = function(id) {
    document.getElementById(id).classList.add('hidden');
    document.body.style.overflow = '';

    // Reset upload state when closing the upload modal
    if (id === 'uploadModal') {
        // Reset UI elements
        const uploadLabel = document.getElementById('uploadLabel');
        const uploadSubmit = document.getElementById('uploadSubmit');
        const progressBarContainer = document.getElementById('progressBarContainer');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const uploadStatus = document.getElementById('uploadStatus');
        const bankStatementFile = document.getElementById('bankStatementFile');

        if (uploadLabel) uploadLabel.classList.remove('hidden');
        if (uploadSubmit) uploadSubmit.classList.add('hidden');
        if (progressBarContainer) progressBarContainer.classList.add('hidden');
        if (progressBar) progressBar.style.width = '0%';
        if (progressPercent) progressPercent.textContent = '0%';
        if (uploadStatus) uploadStatus.textContent = '';
        if (bankStatementFile) bankStatementFile.value = ''; // Clear file input
    }
};

// Upload Modal Trigger
window.openUploadModal = function() {
    // Check if user has account - if yes, skip to upload step
    if (window.userHasAccount) {
        // For existing users, go directly to upload modal
        openModal('uploadModal');
    } else {
        // For new users, start with contact modal
        openModal('contactModal');
    }
};

// File Upload Logic (shared between pages)
function initializeFileUpload() {
    const fileInput = document.getElementById('bankStatementFile');
    const uploadSubmit = document.getElementById('uploadSubmit');
    const uploadForm = document.getElementById('uploadForm');

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];

            if (file && uploadSubmit) {
                uploadSubmit.classList.remove('hidden');
                const uploadStatus = document.getElementById('uploadStatus');
                if (uploadStatus) {
                    uploadStatus.innerHTML = `<span class="text-amber-400 font-bold">File Selected:</span> ${file.name}. Click Upload to analyze.`;
                }
            } else if (uploadSubmit) {
                uploadSubmit.classList.add('hidden');
                const uploadStatus = document.getElementById('uploadStatus');
                if (uploadStatus) uploadStatus.textContent = '';
            }
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Hide form UI and show preloader
            const uploadLabel = document.getElementById('uploadLabel');
            const uploadSubmit = document.getElementById('uploadSubmit');
            const preloader = document.getElementById('preloader');
            const uploadStatus = document.getElementById('uploadStatus');

            if (uploadLabel) uploadLabel.classList.add('hidden');
            if (uploadSubmit) uploadSubmit.classList.add('hidden');
            if (preloader) preloader.classList.remove('hidden');
            if (uploadStatus) uploadStatus.innerHTML = `<span class="text-amber-400 font-bold">Uploading and Analyzing...</span>`;

            // Start dynamic text updates
            startPreloaderTextUpdates();

            const formData = new FormData(uploadForm);

            // Fetch session data and add to formData
            try {
                const sessionResponse = await fetch('get_session.php');
                const sessionData = await sessionResponse.json();

                if (sessionData.email) {
                    formData.append('modal-email', sessionData.email);
                    formData.append('modal-name', sessionData.name);
                    formData.append('modal-income', sessionData.income);
                }
            } catch (error) {
                console.warn('Could not fetch session data:', error);
            }

            fetch('functions/process_upload.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(async (response) => {
                // Always read response as text first
                const text = await response.text();

                // If HTTP status isn't OK, throw an error
                if (!response.ok) {
                    throw new Error(`Server returned status ${response.status} (${response.statusText})`);
                }

                // Try to parse JSON safely
                try {
                    return JSON.parse(text);
                } catch (e) {
                    // If response is not JSON, show the actual server output
                    console.error("Non-JSON server response:", text);

                    // Remove HTML tags from PHP error output (for cleaner display)
                    const cleanText = text.replace(/<[^>]*>?/gm, '').trim();

                    throw new Error(cleanText || "Invalid server response. The system returned HTML instead of JSON.");
                }
            })
            .then(data => {
                if (data.success) {
                    // Decrement additional credits after successful analysis (if user has paid credits)
                    fetch('get_session.php')
                        .then(response => response.json())
                        .then(sessionData => {
                            if (sessionData.additional_credits > 0) {
                                // User has additional credits, decrement them
                                fetch('decrement_credits.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ action: 'decrement' })
                                }).catch(error => {
                                    console.warn('Failed to decrement credits:', error);
                                });
                            }
                        })
                        .catch(error => {
                            console.warn('Failed to check session for credit decrement:', error);
                        });

                    // Success — redirect to summary page
                    window.location.href = 'summary.php';
                } else {
                    // Server returned an error message
                    if (preloader) preloader.classList.add('hidden');
                    if (uploadStatus) uploadStatus.innerHTML = `
                        <span class="text-red-400 font-bold">Upload failed:</span>
                        ${data.message || 'Something went wrong during analysis. Please try again.'}
                    `;
                    if (uploadLabel) uploadLabel.classList.remove('hidden');
                }
            })
            .catch(error => {
                // Hide preloader and show error
                if (preloader) preloader.classList.add('hidden');
                stopPreloaderTextUpdates();

                // Check if it's a 504 timeout error - if so, redirect since data may still be processed
                if (error.message.includes("504")) {
                    // Assume processing succeeded despite timeout, redirect to summary
                    window.location.href = 'summary.php';
                    return;
                }

                let userMessage = "We couldn't complete the upload. Please check your internet connection and try again.";

                // More specific messages based on the error
                if (error.message.includes("Invalid server response")) {
                    userMessage = "A server error occurred — it returned an unexpected response. Please try again.";
                } else if (error.message.includes("Failed to fetch")) {
                    userMessage = "Couldn't connect to the server. Please check your internet connection or try again later.";
                } else if (error.message.includes("status")) {
                    userMessage = `The server responded with an unexpected status. (${error.message})`;
                }

                if (uploadStatus) uploadStatus.innerHTML = `
                    <span class="text-red-400 font-bold">Error:</span> ${userMessage}
                    <br><small class="text-gray-400">${error.message}</small>
                `;

                if (uploadLabel) uploadLabel.classList.remove('hidden');
            });
        });
    }
}

// Copy Referral Link Function
window.copyReferralLink = function() {
    const linkInput = document.getElementById('referralLink') || document.getElementById('dashboardReferralLink');
    const copyButtonText = document.getElementById('copyButtonText') || { textContent: 'Copy Link to Share' };
    const copyStatus = document.getElementById('copyStatus');

    // Use modern clipboard API with fallback
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(linkInput.value).then(() => {
            showCopySuccess(copyButtonText, copyStatus);
        }).catch(() => {
            fallbackCopyTextToClipboard(linkInput.value, copyButtonText, copyStatus);
        });
    } else {
        fallbackCopyTextToClipboard(linkInput.value, copyButtonText, copyStatus);
    }
}

function shareVia(platform) {
    const linkInput = document.getElementById('referralLink') || document.getElementById('dashboardReferralLink');
    const link = linkInput.value;

    const message = encodeURIComponent(
        "I never had the Time to check my bank statements properly to see Why my Money Run Out!\n" +
        "This simple app is helping me see where my Money goes by Analysing my Bank statement within seconds, and Helping me Save!\n" +
        "Use my link to get Free Credits for you.\n💸 Check it out here: " + link
    );

    
    let shareUrl = '';

    switch (platform) {
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${message}`;
            break;
        case 'messenger':
            shareUrl = `fb-messenger://share?link=${encodeURIComponent(link)}&app_id=123456789`; // optional app_id
            break;
        case 'email':
            shareUrl = `mailto:?subject=Awesome Fintech App&body=${message}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${message}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(link)}`;
            break;
        default:
            alert('Sharing platform not supported!');
            return;
    }

    // Open the share URL in a new tab or app
    window.open(shareUrl, '_blank');
}

function nativeShare() {
    const linkInput = document.getElementById('referralLink') || document.getElementById('dashboardReferralLink');
    const link = linkInput.value;

    const message = "I never had the Time to check my bank statements properly to see Why my Money Run Out!\n\n" +
        "This simple app is helping me see where my Money goes by Analysing my Bank statement within seconds, and Helping me Save!\n\n" +
        "Use my link to get Free Credits for you.\n\n💸 Check it out here: " + link;

    if (navigator.share) {
        try {
            navigator.share({
                title: 'Fintech App',
                text: message,
                url: link
            }).then(() => {
                console.log('Successfully shared');
            }).catch((error) => {
                console.error('Error sharing:', error);
            });
        } catch (err) {
            console.error('Sharing failed:', err);
        }
    } else {
        alert('Sharing not supported on this browser.');
    }
}

function fallbackCopyTextToClipboard(text, copyButtonText, copyStatus) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        showCopySuccess(copyButtonText, copyStatus);
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        showCopyError(copyButtonText, copyStatus);
    }

    textArea.remove();
}

function showCopySuccess(copyButtonText, copyStatus) {
    copyButtonText.textContent = 'Copied!';
    if(copyStatus) {
        copyStatus.textContent = 'Link successfully copied to your clipboard!';
        copyStatus.classList.add('text-green-600');
        copyStatus.classList.remove('text-gray-500');
    }

    // Show SweetAlert success message
    Swal.fire({
        icon: 'success',
        title: 'Link Copied!',
        text: 'Your referral link has been copied to clipboard.',
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });

    setTimeout(() => {
        copyButtonText.textContent = 'Copy Link to Share';
        if(copyStatus) {
            copyStatus.textContent = '';
            copyStatus.classList.remove('text-green-600');
            copyStatus.classList.add('text-gray-500');
        }
    }, 3000);
}

function showCopyError(copyButtonText, copyStatus) {
    copyButtonText.textContent = 'Copy Failed';
    if(copyStatus) {
        copyStatus.textContent = 'Failed to copy link. Please try again.';
        copyStatus.classList.add('text-red-600');
        copyStatus.classList.remove('text-gray-500');
    }

    setTimeout(() => {
        copyButtonText.textContent = 'Copy Link to Share';
        if(copyStatus) {
            copyStatus.textContent = '';
            copyStatus.classList.remove('text-red-600');
            copyStatus.classList.add('text-gray-500');
        }
    }, 3000);
}

// Dynamic preloader text updates
let preloaderInterval = null;

function startPreloaderTextUpdates() {
    const preloaderText = document.getElementById('preloaderText');
    if (!preloaderText) return;

    const messages = [
        'Processing your file...',
        'Extracting transaction data...',
        'Analyzing spending patterns...',
        'Categorizing expenses...',
        'Generating insights...',
        'Almost done...'
    ];

    let currentIndex = 0;

    preloaderInterval = setInterval(() => {
        currentIndex = (currentIndex + 1) % messages.length;
        preloaderText.textContent = messages[currentIndex];
    }, 5000); // Change every 5 seconds
}

function stopPreloaderTextUpdates() {
    if (preloaderInterval) {
        clearInterval(preloaderInterval);
        preloaderInterval = null;
    }
}
// Upgrade Plan Function
window.upgradePlan = function(planType) {
    // Show loading state
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait while we upgrade your plan.',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Determine credits to add based on plan
    const creditsToAdd = planType === 'monthly' ? 1 : 12;

    // Update session with new credits (you'll need to implement this on the server side)
    fetch('update_credits.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            plan: planType,
            credits: creditsToAdd
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Upgrade Successful!',
                text: `You've unlocked ${creditsToAdd} more credit${creditsToAdd > 1 ? 's' : ''}! You can now analyze another PDF.`,
                confirmButtonText: 'Continue Analyzing'
            }).then(() => {
                // Redirect back to index.php or wherever the upload modal is
                window.location.href = 'index.php';
            });
        } else {
            // Show error message
            Swal.fire({
                icon: 'error',
                title: 'Upgrade Failed',
                text: data.message || 'Something went wrong. Please try again.',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        console.error('Error upgrading plan:', error);
        console.log('Error details:', error);
        Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Please check your internet connection and try again.',
            confirmButtonText: 'OK'
        });
    });
};
