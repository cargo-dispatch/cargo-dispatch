
class PaginationUtils {
    constructor(options = {}) {
        this.options = {
            containerSelector: '#pagination',
            pageItemClass: 'page-item',
            pageLinkClass: 'page-link',
            activeClass: 'active',
            disabledClass: 'disabled',
            maxVisiblePages: 5,
            ...options
        };
    }

    renderPagination(links, currentPage, lastPage) {
        let paginationHtml = '';
        const current = parseInt(currentPage);
        const total = parseInt(lastPage);
        const maxVisible = this.options.maxVisiblePages;

        // Previous button
        if (current > 1) {
            paginationHtml += `
                <li class="${this.options.pageItemClass}">
                    <a class="${this.options.pageLinkClass}" href="#" data-page="${current - 1}">Prev</a>
                </li>
            `;
        }

        // Always show first page
        paginationHtml += `
            <li class="${this.options.pageItemClass} ${current === 1 ? this.options.activeClass : ''}">
                <a class="${this.options.pageLinkClass}" href="#" data-page="1">1</a>
            </li>
        `;

        // Calculate start and end for visible pages
        let start = Math.max(2, current - Math.floor(maxVisible / 2));
        let end = Math.min(total - 1, start + maxVisible - 1);

        // Adjust start if we're near the end
        start = Math.max(2, end - maxVisible + 1);

        // Left ellipsis
        if (start > 2) {
            paginationHtml += `
                <li class="${this.options.pageItemClass} ${this.options.disabledClass}">
                    <span class="${this.options.pageLinkClass}">...</span>
                </li>
            `;
        }

        // Middle pages
        for (let i = start; i <= end; i++) {
            if (i > 1 && i < total) {
                paginationHtml += `
                    <li class="${this.options.pageItemClass} ${i === current ? this.options.activeClass : ''}">
                        <a class="${this.options.pageLinkClass}" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
        }

        // Right ellipsis
        if (end < total - 1) {
            paginationHtml += `
                <li class="${this.options.pageItemClass} ${this.options.disabledClass}">
                    <span class="${this.options.pageLinkClass}">...</span>
                </li>
            `;
        }

        // Always show last page if there is more than one page
        if (total > 1) {
            paginationHtml += `
                <li class="${this.options.pageItemClass} ${current === total ? this.options.activeClass : ''}">
                    <a class="${this.options.pageLinkClass}" href="#" data-page="${total}">${total}</a>
                </li>
            `;
        }

        // Next button
        if (current < total) {
            paginationHtml += `
                <li class="${this.options.pageItemClass}">
                    <a class="${this.options.pageLinkClass}" href="#" data-page="${current + 1}">Next</a>
                </li>
            `;
        }

        // Wrap in container
        const fullHtml = `
            <div class="pagination-container d-flex justify-content-end">
                <ul class="pagination">${paginationHtml}</ul>
            </div>
        `;

        $(this.options.containerSelector).html(fullHtml);
        this.addPaginationStyles();
    }

    addPaginationStyles() {
        const styles = `
            <style>
                .pagination .page-link {
                    color: #04090fff;
                    border-radius: 4px;
                    margin: 0 2px;
                }

                .pagination .page-item.active .page-link {
                    background-color: #F8C71F;
                    color: white;
                    border-color: #041a31ff;
                }

                .pagination .page-item.disabled .page-link {
                    color: #6c757d;
                    pointer-events: none;
                    cursor: default;
                    background-color: #f8f9fa;
                }
            </style>
        `;
        
        // Only add styles once
        if (!$('#pagination-styles').length) {
            $('head').append(styles.replace('<style>', '<style id="pagination-styles">'));
        }
    }

    // Static method to get page number from URL
    static getPageNumber(url) {
        if (!url) return 1;
        const urlParams = new URLSearchParams(url.split('?')[1]);
        return urlParams.get('page') || 1;
    }

    // Static method to initialize pagination event handlers
    static initPaginationEvents(callback) {
        $(document).off('click', '.page-link').on('click', '.page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && callback) {
                callback(page);
            }
        });
    }
}