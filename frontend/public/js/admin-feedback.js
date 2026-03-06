// Sample feedback data for a specific branch
const feedbackData = [
    {
        id: 1,
        customerName: "John Doe",
        rating: 5,
        service: "Full Body Massage",
        feedback: "Absolutely amazing experience! The masseuse was very skilled and the atmosphere was so relaxing. I fell asleep during the massage and woke up feeling completely refreshed. Will definitely be coming back!",
        date: "2026-03-10",
        time: "2:30 PM"
    },
    {
        id: 2,
        customerName: "Jane Smith",
        rating: 4,
        service: "Facial Treatment",
        feedback: "Great facial! My skin feels so smooth and hydrated. The esthetician was very professional and knowledgeable about the products. Only reason for 4 stars is that the waiting area could be a bit more comfortable.",
        date: "2026-03-09",
        time: "11:00 AM"
    },
    {
        id: 3,
        customerName: "Mike Johnson",
        rating: 5,
        service: "Hot Stone Massage",
        feedback: "Best hot stone massage I've ever had! The stones were perfectly heated and the therapist knew exactly where to apply pressure. The aroma therapy added to the wonderful experience. Highly recommend!",
        date: "2026-03-08",
        time: "4:00 PM"
    },
    {
        id: 4,
        customerName: "Emily Davis",
        rating: 5,
        service: "Hair Color",
        feedback: "Love my new hair color! The stylist listened to exactly what I wanted and gave great recommendations. The salon environment is so cozy and the staff is very friendly. Will definitely return for my next touch-up.",
        date: "2026-03-07",
        time: "1:30 PM"
    },
    {
        id: 5,
        customerName: "Chris Wilson",
        rating: 3,
        service: "Deep Tissue Massage",
        feedback: "The massage was good but the pressure was a bit too light for a deep tissue massage. I asked to adjust it and it improved, but still not as deep as I wanted. The therapist was very professional though.",
        date: "2026-03-06",
        time: "5:00 PM"
    },
    {
        id: 6,
        customerName: "Sarah Brown",
        rating: 5,
        service: "Pedicure",
        feedback: "Excellent pedicure! The nail technician was very thorough and took her time. The polish has been on for a week now and still looks perfect. The spa chairs are so comfortable and the tea was a nice touch.",
        date: "2026-03-05",
        time: "10:00 AM"
    },
    {
        id: 7,
        customerName: "David Lee",
        rating: 4,
        service: "Back Massage",
        feedback: "Great back massage! The therapist focused on my problem areas and relieved a lot of tension. The room was clean and well-maintained. Only suggestion is to add more music options.",
        date: "2026-03-04",
        time: "3:30 PM"
    },
    {
        id: 8,
        customerName: "Lisa Garcia",
        rating: 5,
        service: "Facial Treatment",
        feedback: "Exceptional facial! My skin has never looked better. The esthetician used high-quality products and the facial included a relaxing scalp massage. I felt pampered from start to finish.",
        date: "2026-03-03",
        time: "9:30 AM"
    },
    {
        id: 9,
        customerName: "Robert Taylor",
        rating: 5,
        service: "Hair Cut",
        feedback: "Perfect haircut! The stylist understood exactly what I wanted and executed it flawlessly. The salon has a great vibe and the staff is very welcoming. I've found my new regular barber!",
        date: "2026-03-02",
        time: "2:00 PM"
    },
    {
        id: 10,
        customerName: "Jennifer Martinez",
        rating: 4,
        service: "Manicure",
        feedback: "Very good manicure! The nail technician was skilled and the polish application was smooth. The only issue was that the drying time was a bit longer than expected. Overall, a great experience.",
        date: "2026-03-01",
        time: "11:30 AM"
    },
    {
        id: 11,
        customerName: "James Anderson",
        rating: 5,
        service: "Swedish Massage",
        feedback: "Heavenly Swedish massage! The therapist had the perfect touch - firm but gentle. The aroma therapy and soft music created the ideal relaxation environment. I left feeling completely rejuvenated.",
        date: "2026-02-29",
        time: "4:30 PM"
    },
    {
        id: 12,
        customerName: "Maria Thomas",
        rating: 5,
        service: "Nail Art",
        feedback: "Absolutely stunning nail art! The technician is incredibly talented and created exactly what I wanted. The salon is clean and modern, and the staff is very professional. I've received so many compliments!",
        date: "2026-02-28",
        time: "1:00 PM"
    }
];

let filteredFeedback = [...feedbackData];
let pagination;
window.filteredFeedback = filteredFeedback; // Make filtered feedback globally accessible
window.pagination = pagination; // Make pagination globally accessible

document.addEventListener('DOMContentLoaded', function() {
    // Load feedback after a short delay for loading effect
    setTimeout(loadFeedback, 800);
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        searchFeedback(searchInput.value);
    });
    
    // Filter functionality
    const filterSelect = document.getElementById('filterRating');
    filterSelect.addEventListener('change', function() {
        filterFeedbackByRating(filterSelect.value);
    });
    
    // Calculate and display rating breakdown
    calculateRatingBreakdown();
    
    // Initialize pagination
    pagination = new Pagination({
        totalItems: filteredFeedback.length,
        itemsPerPage: 6,
        currentPage: 1,
        onPageChange: function(page, itemsPerPage) {
            loadFeedback();
        }
    });
    window.pagination = pagination; // Make pagination globally accessible
});

function loadFeedback() {
    const feedbackList = document.getElementById('feedbackList');
    feedbackList.innerHTML = '';
    
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
}

function searchFeedback(searchTerm) {
    const term = searchTerm.toLowerCase();
    filteredFeedback = feedbackData.filter(feedback =>
        feedback.customerName.toLowerCase().includes(term) ||
        feedback.service.toLowerCase().includes(term) ||
        feedback.feedback.toLowerCase().includes(term)
    );
    
    pagination.goToPage(1); // Reset to first page
    loadFeedback();
    calculateRatingBreakdown();
}

function filterFeedbackByRating(filterValue) {
    if (filterValue === 'all') {
        filteredFeedback = [...feedbackData];
    } else {
        const minRating = parseInt(filterValue);
        filteredFeedback = feedbackData.filter(feedback => feedback.rating >= minRating);
    }
    
    pagination.goToPage(1); // Reset to first page
    loadFeedback();
    calculateRatingBreakdown();
}

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


