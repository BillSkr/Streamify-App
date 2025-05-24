// Accordion functionality
function toggleAccordion(header) {
    const content = header.nextElementSibling;
    const isActive = header.classList.contains('active');
    
    // Close all accordion items
    const allHeaders = document.querySelectorAll('.accordion-header');
    const allContents = document.querySelectorAll('.accordion-content');
    
    allHeaders.forEach(h => h.classList.remove('active'));
    allContents.forEach(c => c.classList.remove('active'));
    
    // If the clicked item wasn't active, open it
    if (!isActive) {
        header.classList.add('active');
        content.classList.add('active');
    }
}

// Initialize accordion on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to all accordion headers
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            toggleAccordion(this);
        });
        
        // Add keyboard support
        header.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleAccordion(this);
            }
        });
        
        // Make headers focusable
        header.setAttribute('tabindex', '0');
        header.setAttribute('role', 'button');
        header.setAttribute('aria-expanded', 'false');
    });
    
    // Update aria-expanded when accordion state changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const header = mutation.target;
                const isActive = header.classList.contains('active');
                header.setAttribute('aria-expanded', isActive ? 'true' : 'false');
            }
        });
    });
    
    accordionHeaders.forEach(header => {
        observer.observe(header, { attributes: true });
    });
});