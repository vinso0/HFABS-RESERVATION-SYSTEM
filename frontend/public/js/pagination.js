/**
 * Reusable Pagination Component
 * 
 * Usage:
 * const pagination = new Pagination({
 *     totalItems: 100,
 *     itemsPerPage: 10,
 *     onPageChange: (page, itemsPerPage) => {
 *         // Your callback function
 *         console.log('Page:', page, 'Items per page:', itemsPerPage);
 *     }
 * });
 */

class Pagination {
    constructor(config) {
        this.totalItems = config.totalItems || 0;
        this.itemsPerPage = config.itemsPerPage || 10;
        this.currentPage = config.currentPage || 1;
        this.maxVisiblePages = config.maxVisiblePages || 5;
        this.onPageChange = config.onPageChange || function() {};
        
        this.init();
    }

    init() {
        // Set up event listeners
        const itemsPerPageSelect = document.getElementById('itemsPerPage');
        if (itemsPerPageSelect) {
            itemsPerPageSelect.value = this.itemsPerPage;
            itemsPerPageSelect.addEventListener('change', (e) => {
                this.itemsPerPage = parseInt(e.target.value);
                this.currentPage = 1; // Reset to first page
                this.render();
                this.onPageChange(this.currentPage, this.itemsPerPage);
            });
        }

        this.render();
    }

    getTotalPages() {
        return Math.ceil(this.totalItems / this.itemsPerPage);
    }

    getStartIndex() {
        return (this.currentPage - 1) * this.itemsPerPage + 1;
    }

    getEndIndex() {
        const end = this.currentPage * this.itemsPerPage;
        return end > this.totalItems ? this.totalItems : end;
    }

    goToPage(page) {
        const totalPages = this.getTotalPages();

        if (page < 1) page = 1;
        // Only clamp to totalPages if totalPages > 0, otherwise stay at 1
        if (totalPages > 0 && page > totalPages) page = totalPages;

        this.currentPage = page;
        this.render();
        this.onPageChange(this.currentPage, this.itemsPerPage);
    }

    nextPage() {
        this.goToPage(this.currentPage + 1);
    }

    previousPage() {
        this.goToPage(this.currentPage - 1);
    }

    render() {
        this.updateInfo();
        this.updateControls();
    }

    updateInfo() {
        const startEl = document.getElementById('paginationStart');
        const endEl = document.getElementById('paginationEnd');
        const totalEl = document.getElementById('paginationTotal');

        if (startEl) startEl.textContent = this.totalItems > 0 ? this.getStartIndex() : 0;
        if (endEl) endEl.textContent = this.getEndIndex();
        if (totalEl) totalEl.textContent = this.totalItems;
    }

    updateControls() {
        const controlsContainer = document.getElementById('paginationControls');
        if (!controlsContainer) return;

        controlsContainer.innerHTML = '';

        const totalPages = this.getTotalPages();
        if (totalPages <= 1) return; // Don't show pagination if only 1 page

        // Previous button
        const prevBtn = this.createButton('prev', '<i class="fas fa-chevron-left"></i>');
        prevBtn.disabled = this.currentPage === 1;
        prevBtn.addEventListener('click', () => this.previousPage());
        controlsContainer.appendChild(prevBtn);

        // Page numbers
        const pageNumbers = this.getPageNumbers();
        pageNumbers.forEach(num => {
            if (num === '...') {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.textContent = '...';
                controlsContainer.appendChild(ellipsis);
            } else {
                const pageBtn = this.createPageNumber(num);
                pageBtn.addEventListener('click', () => this.goToPage(num));
                controlsContainer.appendChild(pageBtn);
            }
        });

        // Next button
        const nextBtn = this.createButton('next', '<i class="fas fa-chevron-right"></i>');
        nextBtn.disabled = this.currentPage === totalPages;
        nextBtn.addEventListener('click', () => this.nextPage());
        controlsContainer.appendChild(nextBtn);
    }

    createButton(type, content) {
        const btn = document.createElement('button');
        btn.className = 'pagination-btn';
        btn.innerHTML = content;
        btn.setAttribute('aria-label', type === 'prev' ? 'Previous page' : 'Next page');
        return btn;
    }

    createPageNumber(num) {
        const btn = document.createElement('button');
        btn.className = 'pagination-number';
        if (num === this.currentPage) {
            btn.classList.add('active');
        }
        btn.textContent = num;
        btn.setAttribute('aria-label', `Go to page ${num}`);
        return btn;
    }

    getPageNumbers() {
        const totalPages = this.getTotalPages();
        const current = this.currentPage;
        const maxVisible = this.maxVisiblePages;
        const pages = [];

        if (totalPages <= maxVisible) {
            // Show all pages if total is less than max visible
            for (let i = 1; i <= totalPages; i++) {
                pages.push(i);
            }
        } else {
            // Always show first page
            pages.push(1);

            // Calculate range around current page
            let start = Math.max(2, current - Math.floor(maxVisible / 2));
            let end = Math.min(totalPages - 1, start + maxVisible - 3);

            // Adjust start if we're near the end
            if (end === totalPages - 1) {
                start = Math.max(2, end - maxVisible + 3);
            }

            // Add ellipsis if needed
            if (start > 2) {
                pages.push('...');
            }

            // Add page numbers
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            // Add ellipsis if needed
            if (end < totalPages - 1) {
                pages.push('...');
            }

            // Always show last page
            pages.push(totalPages);
        }

        return pages;
    }

    // Public method to update total items (useful when data changes)
    updateTotalItems(total) {
        this.totalItems = total;

        const totalPages = this.getTotalPages();

        // If current page is now out of bounds, clamp it — but never below 1
        if (this.currentPage > totalPages && totalPages > 0) {
            this.currentPage = totalPages;
        } else if (totalPages === 0) {
            // Don't set to 0 — keep at 1 so next load with data works correctly
            this.currentPage = 1;
        }

        this.render();
    }

    // Public method to get current page data range
    getCurrentPageRange() {
        return {
            start: this.getStartIndex() - 1, // 0-indexed for array slicing
            end: this.getEndIndex()
        };
    }
}
