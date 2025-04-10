/**
 * Counter Animation Script
 * Animates the counting of numbers when they come into view
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get all counter elements
    const counters = document.querySelectorAll('.stats-section__number');
    
    // Check if counters exist on the page
    if (counters.length === 0) return;
    
    // Flag to ensure animation only runs once
    let counterAnimationStarted = false;
    
    // Function to animate counting
    function animateCounters() {
        counters.forEach(counter => {
            // Get the target number to count to
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 2000; // Animation duration in milliseconds
            
            // Add animation class
            counter.classList.add('animate');
            
            // Set starting count
            let count = 0;
            
            // Calculate increment step based on target and duration
            const increment = Math.ceil(target / (duration / 30)); // Update roughly every 30ms
            
            // Start the counter
            const timer = setInterval(() => {
                count += increment;
                
                // If we've reached or exceeded the target, set to target and clear interval
                if (count >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = count;
                }
            }, 30);
        });
        
        // Set flag to indicate animation has run
        counterAnimationStarted = true;
    }
    
    // Intersection Observer to detect when counters are visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // If element is in view and animation hasn't run yet
            if (entry.isIntersecting && !counterAnimationStarted) {
                animateCounters();
                // Optionally stop observing after animation starts
                observer.disconnect();
            }
        });
    }, {
        root: null, // Use viewport as root
        threshold: 0.3 // Trigger when at least 30% of the element is visible
    });
    
    // Observe the first counter (we only need to observe one to trigger all)
    if (counters[0]) {
        observer.observe(counters[0].closest('.stats-section'));
    }
    
    // Fallback for browsers that don't support Intersection Observer
    if (!('IntersectionObserver' in window)) {
        // Simple check if element is in viewport
        function isInViewport(element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }
        
        // Check on scroll
        window.addEventListener('scroll', function() {
            if (!counterAnimationStarted && isInViewport(counters[0].closest('.stats-section'))) {
                animateCounters();
            }
        });
        
        // Initial check on page load
        if (isInViewport(counters[0].closest('.stats-section'))) {
            animateCounters();
        }
    }
});