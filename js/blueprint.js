// Blueprint page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize blueprint chart
    renderBlueprintChart();

    // Set up PDF download functionality
    const downloadBtn = document.getElementById('downloadPdfButton');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', generatePdf);
    }

    // Auto-detect country for blueprint modal (only if modal exists)
    const modal = document.getElementById('blueprintModal');
    if (modal) {
        detectUserCountry();
    }
});

// Modal functions for blueprint page
function closeBlueprintModal() {
    const modal = document.getElementById('blueprintModal');
    if (modal) {
        modal.remove();
    }
}

// Remove the JavaScript submit handler since we're using form action now

async function detectUserCountry_bkup() {
    // Show loading state
    const countrySelect = document.getElementById('user_country');
    if (countrySelect) {
        // Add loading option
        const loadingOption = document.createElement('option');
        loadingOption.value = '';
        loadingOption.textContent = 'Detecting your location...';
        loadingOption.disabled = true;
        loadingOption.selected = true;
        countrySelect.appendChild(loadingOption);
    }

    try {
        // Request location permission and get precise location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async (position) => {
                const { latitude, longitude } = position.coords;

                try {
                    const response = await fetch(`/get_user_country.php?lat=${latitude}&lon=${longitude}`);
                    const data = await response.json();

                    if (data.success && data.country_code) {
                        if (countrySelect) {
                            // Remove loading option
                            const loadingOption = countrySelect.querySelector('option[disabled]');
                            if (loadingOption) loadingOption.remove();

                            // Select the detected country
                            countrySelect.value = data.country_code;

                            // Add success feedback
                            showLocationSuccess();
                        }
                        return;
                    }
                } catch (error) {
                    console.error('Error with coordinates:', error);
                }

                // Fallback to IP-based detection
                fallbackIPDetection();
            }, (error) => {
                console.warn('Geolocation failed:', error.message);
                // Show permission denied message and fallback
                showLocationError(error.code);
                fallbackIPDetection();
            }, {
                // Geolocation options for better accuracy
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5 minutes
            });
        } else {
            console.warn('Geolocation not supported');
            showLocationError('not_supported');
            fallbackIPDetection();
        }
    } catch (error) {
        console.error('Error detecting country:', error);
        showLocationError('unknown');
        fallbackCountryDetection();
    }
}

async function detectUserCountry() {
    // Get user's timezone for better accuracy
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    // Use server-side detection to avoid CORS issues
    fetch(`get_user_country.php?tz=${encodeURIComponent(timezone)}`)
    .then(res => res.json())
    .then(data => {
        if (data.success && data.country_code) {
            const countrySelect = document.getElementById('user_country');
            if (countrySelect) {
                countrySelect.value = data.country_code;
                console.log(`Country detected: ${data.country_code} (method: ${data.method})`);
            }
        } else {
            console.warn("Couldn't detect location automatically.");
            // Fallback to timezone detection
            fallbackCountryDetection();
        }
    })
    .catch(() => {
        console.warn("Couldn't detect location automatically.");
        // Fallback to timezone detection
        fallbackCountryDetection();
    });
}


function showLocationSuccess() {
    // Optional: Show a brief success message
    const countrySelect = document.getElementById('user_country');
    if (countrySelect) {
        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        console.log(`Location detected: ${selectedOption.textContent}`);
    }
}

function showLocationError(errorCode) {
    let message = 'Unable to detect your location. Please select your country manually.';

    switch(errorCode) {
        case 1: // PERMISSION_DENIED
            message = 'Location permission denied. Please select your country manually or allow location access.';
            break;
        case 2: // POSITION_UNAVAILABLE
            message = 'Location information unavailable. Please select your country manually.';
            break;
        case 3: // TIMEOUT
            message = 'Location request timed out. Please select your country manually.';
            break;
        case 'not_supported':
            message = 'Location services not supported by your browser. Please select your country manually.';
            break;
    }

    // Remove loading option and show error
    const countrySelect = document.getElementById('user_country');
    if (countrySelect) {
        const loadingOption = countrySelect.querySelector('option[disabled]');
        if (loadingOption) loadingOption.remove();

        // Reset to default
        countrySelect.value = '';
    }

    console.warn(message);
}

async function fallbackIPDetection() {
    try {
        const response = await fetch('/get_user_country.php');
        const data = await response.json();

        if (data.success && data.country_code) {
            const countrySelect = document.getElementById('user_country');
            if (countrySelect) {
                countrySelect.value = data.country_code;
            }
        } else {
            // Final fallback to timezone detection
            fallbackCountryDetection();
        }
    } catch (error) {
        console.error('Error with IP detection:', error);
        // Final fallback to timezone detection
        fallbackCountryDetection();
    }
}

function fallbackCountryDetection() {
    // Simple country detection based on timezone
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    // Basic mapping - you can enhance this
    const countryMap = {
        'Asia/Dhaka': 'BD',
        'America/New_York': 'US',
        'America/Toronto': 'CA',
        'Europe/London': 'UK',
        'Australia/Sydney': 'AU',
        'Europe/Berlin': 'DE',
        'Europe/Paris': 'FR'
    };

    const detectedCountry = countryMap[timezone] || 'US';
    const countrySelect = document.getElementById('user_country');
    if (countrySelect) {
        countrySelect.value = detectedCountry;
    }
}

// Render the blueprint chart with dynamic colors
function renderBlueprintChart() {
    const chartRing = document.getElementById('blueprintChartRing');
    if (!chartRing) {
        console.error('blueprintChartRing element not found');
        return;
    }

    // Get data from PHP-generated content
    const needsElement = document.getElementById('needsActual');
    const wantsElement = document.getElementById('wantsActual');
    const saveElement = document.getElementById('saveActual');

    // Get currency from PHP
    const currency = '<?php echo $currency ?? "USD"; ?>';

    if (!needsElement || !wantsElement || !saveElement) {
        console.error('Chart data elements not found');
        return;
    }

    const needsPercent = parseFloat(needsElement.textContent.match(/(\d+\.?\d*)/)?.[1] || 0);
    const wantsPercent = parseFloat(wantsElement.textContent.match(/(\d+\.?\d*)/)?.[1] || 0);
    const savePercent = parseFloat(saveElement.textContent.match(/(\d+\.?\d*)/)?.[1] || 0);

    // Create conic gradient for the chart
    const gradient = `conic-gradient(
        #10b981 0% ${needsPercent.toFixed(2)}%,
        #f59e0b ${needsPercent.toFixed(2)}% ${(needsPercent + wantsPercent).toFixed(2)}%,
        #3b82f6 ${(needsPercent + wantsPercent).toFixed(2)}% ${(needsPercent + wantsPercent + savePercent).toFixed(2)}%,
        #e5e7eb ${(needsPercent + wantsPercent + savePercent).toFixed(2)}% 100%
    )`;

    chartRing.style.background = gradient;
}

// Generate PDF from blueprint content
async function generatePdf_bkup() {
    const downloadBtn = document.getElementById('downloadPdfButton');
    const content = document.getElementById('blueprint-content-container');

    if (!downloadBtn || !content) return;

    // Update button state
    const originalText = downloadBtn.textContent;
    downloadBtn.textContent = 'Generating PDF...';
    downloadBtn.disabled = true;

    try {
        // Check if html2canvas and jsPDF are available
        if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
            throw new Error('PDF generation libraries not loaded');
        }

        // Generate canvas from content
        const canvas = await html2canvas(content, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff'
        });

        // Create PDF
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const imgData = canvas.toDataURL('image/png');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();

        // Calculate dimensions to fit the content
        const imgWidth = canvas.width;
        const imgHeight = canvas.height;
        const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
        const imgX = (pdfWidth - imgWidth * ratio) / 2;
        const imgY = 0;

        pdf.addImage(imgData, 'PNG', imgX, imgY, imgWidth * ratio, imgHeight * ratio);

        // Save the PDF
        pdf.save('Personalized_Wealth_Blueprint.pdf');

    } catch (error) {
        console.error('PDF generation failed:', error);

        // Show error message
        const errorMsg = document.createElement('div');
        errorMsg.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        errorMsg.textContent = 'Failed to generate PDF. Please try again.';
        document.body.appendChild(errorMsg);

        setTimeout(() => {
            errorMsg.remove();
        }, 3000);

    } finally {
        // Reset button state
        downloadBtn.textContent = originalText;
        downloadBtn.disabled = false;
    }
}

async function generatePdf_old() {
    const downloadBtn = document.getElementById('downloadPdfButton');
    const content = document.getElementById('blueprint-content-container');
    const elementsToHide = document.querySelectorAll('.pdf-hide');

    if (!downloadBtn || !content) return;

    const originalText = downloadBtn.textContent;
    downloadBtn.textContent = 'Generating PDF...';
    downloadBtn.disabled = true;

    try {
        // 🔹 Hide elements before generating
        elementsToHide.forEach(el => el.style.display = 'none');

        // Generate canvas
        const canvas = await html2canvas(content, {
            scale: 1.2, // reduce scale for smaller size
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff'
        });

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const imgData = canvas.toDataURL('image/jpeg', 0.4); // JPEG + compression
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();

        const imgWidth = canvas.width;
        const imgHeight = canvas.height;
        const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
        const imgX = (pdfWidth - imgWidth * ratio) / 2;
        const imgY = 0;

        pdf.addImage(imgData, 'JPEG', imgX, imgY, imgWidth * ratio, imgHeight * ratio);

        pdf.save('Personalized_Wealth_Blueprint.pdf');

    } catch (error) {
        console.error('PDF generation failed:', error);
        alert('Failed to generate PDF. Please try again.');
    } finally {
        // 🔹 Show elements back
        elementsToHide.forEach(el => el.style.display = '');
        downloadBtn.textContent = originalText;
        downloadBtn.disabled = false;
    }
}

async function generatePdf() {
    const downloadBtn = document.getElementById('downloadPdfButton');
    const sections = document.querySelectorAll('#blueprint-content-container > section, #blueprint-content-container > header');

    if (!downloadBtn || !sections.length) return;

    const originalText = downloadBtn.textContent;
    downloadBtn.textContent = 'Generating PDF...';
    downloadBtn.disabled = true;

    try {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        const margin = 10;

        let positionY = margin;

        for (let i = 0; i < sections.length; i++) {
            const canvas = await html2canvas(sections[i], { scale: 2, useCORS: true, allowTaint: true, backgroundColor: '#fff' });
            const imgData = canvas.toDataURL('image/jpeg', 0.9);

            const contentWidth = pdfWidth - 2 * margin;
            const contentHeight = (canvas.height * contentWidth) / canvas.width;

            if (positionY + contentHeight > pdfHeight) {
                pdf.addPage();
                positionY = margin;
            }

            pdf.addImage(imgData, 'JPEG', margin, positionY, contentWidth, contentHeight);
            positionY += contentHeight + 5; // small gap between sections
        }

        pdf.save(`Wealth_Blueprint-${new Date().toISOString().split('T')[0]}.pdf`);

    } catch (err) {
        console.error('PDF generation failed:', err);
        alert('Failed to generate PDF. Check console.');
    } finally {
        downloadBtn.textContent = originalText;
        downloadBtn.disabled = false;
    }
}

// Utility function to format currency
function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Utility function to format percentage
function formatPercent(value) {
    return value.toFixed(1) + '%';
}