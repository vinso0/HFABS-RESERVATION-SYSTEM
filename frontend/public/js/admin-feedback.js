// API base URL - adjust based on your environment (using URL param format)
const API_BASE_URL = '../../backend/public/index.php?url=feedback';

// Store fetched feedback data
let feedbackData = [];
let filteredFeedback = [];
let pagination;

// Fetch feedback from API
async function fetchFeedback(search = '', minRating = 0) {
    let url = `${API_BASE_URL}&search=${encodeURIComponent(search)}&min_rating=${encodeURIComponent(minRating)}`;
    
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include'
    });

    const responseText = await response.text();
    
    if (!response.ok) {
        throw new Error(`Server error: ${response.status} - ${responseText}`);
    }

    try {
        const result = JSON.parse(responseText);

        if (result.success) {
            feedbackData = result.data.map(feedback => ({
                id: feedback.id,
                customerName: feedback.customerName,
                rating: feedback.rating,
                service: feedback.service,
                feedback: feedback.feedback,
                date: feedback.date,
                time: feedback.time
            }));
            return result;
        } else {
            throw new Error(result.message || 'Failed to fetch feedback');
        }
    } catch (e) {
        if (e instanceof SyntaxError) {
            throw new Error('Invalid JSON response: ' + responseText);
        }
        throw e;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize pagination first
    pagination = new Pagination({
        totalItems: 0,
        itemsPerPage: 6,
        currentPage: 1,
        onPageChange: function(page, itemsPerPage) {
            // Get current filter values when page changes
            const searchInput = document.getElementById('searchInput');
            const filterSelect = document.getElementById('filterRating');
            const searchTerm = searchInput?.value || '';
            const filterValue = filterSelect?.value || 'all';
            const minRating = filterValue === 'all' ? 0 : parseInt(filterValue);
            loadFeedback(searchTerm, minRating);
        }
    });
    window.pagination = pagination;
    
    // Load feedback immediately after pagination is set up
    // Use setTimeout with 0 delay to run after DOMContentLoaded completes
    setTimeout(async () => {
        try {
            const searchTerm = '';
            const minRating = 0;
            await loadFeedback(searchTerm, minRating);
        } catch (error) {
            const feedbackList = document.getElementById('feedbackList');
            if (feedbackList) {
                feedbackList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Failed to load feedback</p>
                        <p class="subtitle">Please try again later</p>
                    </div>
                `;
            }
        }
    }, 0);
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        // Debounce search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchFeedback(searchInput.value);
        }, 300);
    });
    
    // Filter functionality
    const filterSelect = document.getElementById('filterRating');
    filterSelect.addEventListener('change', function() {
        filterFeedbackByRating(filterSelect.value);
    });
});

async function loadFeedback(searchTerm = '', minRating = 0) {
    const feedbackList = document.getElementById('feedbackList');
    if (!feedbackList) return;
    
    feedbackList.innerHTML = `
        <div class="loading-row">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Loading feedback...</span>
            </div>
        </div>
    `;
    
    try {
        const result = await fetchFeedback(searchTerm, minRating);
        filteredFeedback = [...feedbackData];
        
        // Update rating breakdown with stats from API
        if (result.stats) {
            updateRatingBreakdown(result.stats);
        } else {
            calculateRatingBreakdown();
        }
        
        if (filteredFeedback.length === 0) {
            feedbackList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>No feedback found</p>
                    <p class="subtitle">Try adjusting your search or filters</p>
                </div>
            `;
            pagination.updateTotalItems(0);
            return;
        }
        
        // Clear loading and display feedback
        feedbackList.innerHTML = '';
        
        // Get current page range from pagination
        const range = pagination.getCurrentPageRange();
        const feedbackToDisplay = filteredFeedback.slice(range.start, range.end);
        
        feedbackToDisplay.forEach(feedback => {
            const feedbackCard = document.createElement('div');
            feedbackCard.className = 'feedback-card';
            feedbackCard.innerHTML = `
                <div class="feedback-header">
                    <div class="feedback-user">
                        <div class="customer-name">${feedback.customerName}</div>
                        <div class="feedback-time">${formatDateTime(feedback.date, feedback.time)}</div>
                    </div>
                    <div class="feedback-rating">
                        <div class="feedback-stars">${generateStars(feedback.rating)}</div>
                        <div class="feedback-service">
                            <i class="fas fa-spa"></i>
                            <span>${feedback.service}</span>
                        </div>
                    </div>
                </div>
                <div class="feedback-content">
                    <div class="feedback-text">${feedback.feedback}</div>
                </div>
            `;
            feedbackList.appendChild(feedbackCard);
        });
        
        // Update pagination
        pagination.updateTotalItems(filteredFeedback.length);
    } catch (error) {
        feedbackList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-circle"></i>
                <p>Failed to load feedback</p>
                <p class="subtitle">Please try again later</p>
            </div>
        `;
    }
}

// Search functionality
function searchFeedback(searchTerm) {
    const filterValue = document.getElementById('filterRating')?.value || 'all';
    const minRating = filterValue === 'all' ? 0 : parseInt(filterValue);
    
    pagination.goToPage(1); // Reset to first page
    loadFeedback(searchTerm, minRating);
}

// Filter functionality
function filterFeedbackByRating(filterValue) {
    const searchTerm = document.getElementById('searchInput')?.value || '';
    const minRating = filterValue === 'all' ? 0 : parseInt(filterValue);
    
    pagination.goToPage(1); // Reset to first page
    loadFeedback(searchTerm, minRating);
}

// Calculate rating breakdown from filtered data
function calculateRatingBreakdown() {
    // Calculate overall rating
    const totalRating = filteredFeedback.reduce((sum, feedback) => sum + feedback.rating, 0);
    const overallRating = filteredFeedback.length > 0 ? (totalRating / filteredFeedback.length).toFixed(1) : '0.0';
    
    // Calculate rating distribution
    const ratingCounts = {
        5: 0,
        4: 0,
        3: 0,
        2: 0,
        1: 0
    };
    
    filteredFeedback.forEach(feedback => {
        ratingCounts[feedback.rating]++;
    });
    
    // Update UI
    document.getElementById('overallRating').textContent = overallRating;
    document.getElementById('totalReviews').textContent = `${filteredFeedback.length} review${filteredFeedback.length !== 1 ? 's' : ''}`;
    
    // Update stars based on rating
    const starContainer = document.getElementById('overallStars');
    starContainer.innerHTML = generateStars(parseFloat(overallRating));
    
    // Update rating breakdown
    const breakdownContainer = document.getElementById('ratingBreakdown');
    breakdownContainer.innerHTML = '';
    
    for (let i = 5; i >= 1; i--) {
        const percentage = filteredFeedback.length > 0 ? Math.round((ratingCounts[i] / filteredFeedback.length) * 100) : 0;
        
        const barContainer = document.createElement('div');
        barContainer.className = 'rating-bar-container';
        barContainer.innerHTML = `
            <div class="rating-number">${i} <i class="fas fa-star"></i></div>
            <div class="rating-bar">
                <div class="rating-progress" style="width: ${percentage}%"></div>
            </div>
            <div class="rating-count">${ratingCounts[i]}</div>
        `;
        
        breakdownContainer.appendChild(barContainer);
    }
}

// Update rating breakdown with stats from API
function updateRatingBreakdown(stats) {
    const overallRating = parseFloat(stats.averageRating).toFixed(1);
    const totalReviews = stats.totalReviews || 0;
    
    // Update UI
    document.getElementById('overallRating').textContent = overallRating;
    document.getElementById('totalReviews').textContent = `${totalReviews} review${totalReviews !== 1 ? 's' : ''}`;
    
    // Update stars based on rating
    const starContainer = document.getElementById('overallStars');
    starContainer.innerHTML = generateStars(parseFloat(overallRating));
    
    // Update rating breakdown
    const breakdownContainer = document.getElementById('ratingBreakdown');
    breakdownContainer.innerHTML = '';
    
    const ratingCounts = {
        5: parseInt(stats.fiveStars) || 0,
        4: parseInt(stats.fourStars) || 0,
        3: parseInt(stats.threeStars) || 0,
        2: parseInt(stats.twoStars) || 0,
        1: parseInt(stats.oneStar) || 0
    };
    
    for (let i = 5; i >= 1; i--) {
        const percentage = totalReviews > 0 ? Math.round((ratingCounts[i] / totalReviews) * 100) : 0;
        
        const barContainer = document.createElement('div');
        barContainer.className = 'rating-bar-container';
        barContainer.innerHTML = `
            <div class="rating-number">${i} <i class="fas fa-star"></i></div>
            <div class="rating-bar">
                <div class="rating-progress" style="width: ${percentage}%"></div>
            </div>
            <div class="rating-count">${ratingCounts[i]}</div>
        `;
        
        breakdownContainer.appendChild(barContainer);
    }
}

function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let stars = '';
    
    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    
    if (hasHalfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }
    
    return stars;
}

function formatDateTime(dateString, timeString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = date.toLocaleDateString('en-US', options);
    return `${formattedDate} at ${timeString}`;
}

