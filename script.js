document.addEventListener('DOMContentLoaded', () => {
    // 1. Custom Cursor Glow Movement
    const cursorGlow = document.querySelector('.cursor-glow');
    document.addEventListener('mousemove', (e) => {
        cursorGlow.style.left = `${e.clientX}px`;
        cursorGlow.style.top = `${e.clientY}px`;
    });

    // 2. Interactive Parallax Card Movement
    const visualWrapper = document.querySelector('.hero-visual');
    const floatingBadges = document.querySelectorAll('.hotspot-badge');

    if (visualWrapper) {
        visualWrapper.addEventListener('mousemove', (e) => {
            const { width, height, left, top } = visualWrapper.getBoundingClientRect();
            const mouseX = e.clientX - left - width / 2;
            const mouseY = e.clientY - top - height / 2;

            // Apply a subtle parallax rotation to the background container
            visualWrapper.style.transform = `perspective(1000px) rotateY(${mouseX * 0.01}deg) rotateX(${-mouseY * 0.01}deg)`;

            // Make badges float slightly toward/away from the cursor
            floatingBadges.forEach((badge) => {
                const speed = 0.03;
                badge.style.transform = `translate(${mouseX * speed}px, ${mouseY * speed}px)`;
            });
        });

        visualWrapper.addEventListener('mouseleave', () => {
            visualWrapper.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
            floatingBadges.forEach((badge) => {
                badge.style.transform = 'translate(0px, 0px)';
            });
        });
    }    // 3. Hamburger Menu Toggle
    const mobileMenu = document.getElementById('mobile-menu');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenu && navMenu) {
        mobileMenu.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }
    // 4. Smooth Entrance Load Animation
    const elementsToAnimate = [
        '.hero-title',
        '.hero-subtitle',
        '.hero-actions',
        '.trust-badges',
        '.hero-visual'
    ];

    let delayIndex = 0;
    elementsToAnimate.forEach((selector) => {
        const el = document.querySelector(selector);
        if (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1)';
            
            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 120 * delayIndex);
            delayIndex++;
        }
    });

    // 5. Generalized Infinite Carousel Logic (Mobile/Tablet View)
    function initInfiniteCarousel(sliderSelector, dotsContainerSelector, cardSelector) {
        const slider = document.querySelector(sliderSelector);
        const dotsContainer = document.querySelector(dotsContainerSelector);
        if (!slider || !dotsContainer) return;

        const originalCards = Array.from(slider.querySelectorAll(cardSelector));
        const originalCount = originalCards.length;
        if (originalCount === 0) return;
        let scrollInterval;
        
        const getCardWidth = () => {
            const firstCard = slider.querySelector(cardSelector);
            if (!firstCard) return 0;
            const style = window.getComputedStyle(slider);
            const gap = parseFloat(style.columnGap || style.gap) || 20;
            return firstCard.offsetWidth + gap;
        };

        // Clone cards for infinite loop: clone 4 cards to beginning and 4 cards to end to avoid hitting scroll limits
        const numClones = 4;
        // Append clones to the end
        for (let i = 0; i < numClones; i++) {
            const clone = originalCards[i % originalCount].cloneNode(true);
            clone.classList.add('clone');
            slider.appendChild(clone);
        }
        // Prepend clones to the beginning
        for (let i = 0; i < numClones; i++) {
            const index = (originalCount - numClones + i + originalCount * 2) % originalCount;
            const clone = originalCards[index].cloneNode(true);
            clone.classList.add('clone');
            slider.insertBefore(clone, slider.firstChild);
        }

        // Set initial scroll to first real card
        const initScrollPosition = () => {
            if (window.innerWidth <= 768) {
                const cardWidth = getCardWidth();
                slider.scrollLeft = cardWidth * numClones;
            }
        };

        // Run scroll placement
        initScrollPosition();
        window.addEventListener('resize', initScrollPosition);

        // Create pagination dots
        dotsContainer.innerHTML = '';
        originalCards.forEach((_, idx) => {
            const dot = document.createElement('div');
            dot.classList.add('carousel-dot');
            if (idx === 0) dot.classList.add('active');
            
            dot.addEventListener('click', () => {
                stopAutoSlide();
                const cardWidth = getCardWidth();
                slider.scrollTo({ left: (idx + numClones) * cardWidth, behavior: 'smooth' });
                setTimeout(startAutoSlide, 2000);
            });
            dotsContainer.appendChild(dot);
        });

        // Loop border checking instantly without smooth transition when crossing cloned cards
        const handleScrollAndLoop = () => {
            if (window.innerWidth > 768) return;
            const cardWidth = getCardWidth();
            if (!cardWidth) return;

            const scrollLeft = slider.scrollLeft;
            const startBoundary = cardWidth * numClones;
            const endBoundary = cardWidth * (originalCount + numClones);

            // Left boundary check
            if (scrollLeft <= (numClones - 1) * cardWidth) {
                slider.scrollLeft = scrollLeft + (originalCount * cardWidth);
            }
            // Right boundary check
            else if (scrollLeft >= endBoundary) {
                slider.scrollLeft = scrollLeft - (originalCount * cardWidth);
            }

            // Sync dots
            const relativeScroll = slider.scrollLeft - startBoundary;
            let activeIndex = Math.round(relativeScroll / cardWidth);
            if (activeIndex < 0) activeIndex = originalCount - 1;
            if (activeIndex >= originalCount) activeIndex = 0;

            const dots = dotsContainer.querySelectorAll('.carousel-dot');
            dots.forEach((dot, idx) => {
                if (idx === activeIndex) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        };

        const startAutoSlide = () => {
            stopAutoSlide();
            scrollInterval = setInterval(() => {
                if (window.innerWidth > 768) return; // Only slide on mobile/tablet view
                const cardWidth = getCardWidth();
                if (!cardWidth) return;
                
                // Scroll smoothly to next card
                slider.scrollBy({ left: cardWidth, behavior: 'smooth' });
            }, 5000);
        };

        const stopAutoSlide = () => {
            if (scrollInterval) {
                clearInterval(scrollInterval);
                scrollInterval = null;
            }
        };

        // Initialize auto slide
        startAutoSlide();

        // Listen for scroll & loop triggers
        slider.addEventListener('scroll', handleScrollAndLoop, { passive: true });

        // Pause auto-sliding on touch start/interaction and resume on end
        slider.addEventListener('touchstart', stopAutoSlide, { passive: true });
        slider.addEventListener('touchend', startAutoSlide, { passive: true });
        
        // Also pause on mouse hover (if testing on desktop with responsive view)
        slider.addEventListener('mouseenter', stopAutoSlide);
        slider.addEventListener('mouseleave', startAutoSlide);
    }

    // Initialize carousels for Services and Industries sections
    initInfiniteCarousel('.services-grid-container', '.services-section .carousel-dots', '.service-card-new');
    initInfiniteCarousel('.industries-grid', '.industries-section .carousel-dots', '.industry-card');

    // Header Scroll Effect
    const header = document.querySelector('.header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Initial trigger in case of refresh when already scrolled
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        }
    }

    // 6. Social Share Section Dynamic Hrefs & Copy Link
    const shareLinkedIn = document.getElementById('share-linkedin');
    const shareFacebook = document.getElementById('share-facebook');
    const shareWhatsApp = document.getElementById('share-whatsapp');
    const copyShareBtn = document.getElementById('copy-share-link');
    const copyTooltip = document.getElementById('copy-tooltip');

    if (shareLinkedIn || shareFacebook || shareWhatsApp || copyShareBtn) {
        const currentUrl = window.location.href;
        const pageTitle = document.title;

        if (shareLinkedIn) {
            shareLinkedIn.href = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(currentUrl)}`;
        }
        if (shareFacebook) {
            shareFacebook.href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`;
        }
        if (shareWhatsApp) {
            shareWhatsApp.href = `https://api.whatsapp.com/send?text=${encodeURIComponent(pageTitle + ' - ' + currentUrl)}`;
        }
        if (copyShareBtn && copyTooltip) {
            copyShareBtn.addEventListener('click', (e) => {
                e.preventDefault();
                navigator.clipboard.writeText(currentUrl).then(() => {
                    copyTooltip.classList.add('show');
                    setTimeout(() => {
                        copyTooltip.classList.remove('show');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            });
        }
    }

    // 7. Products Page Sidebar Interactions (Collapsible Filters & Category Switching)
    const filterHeaders = document.querySelectorAll('.filter-group-header');
    filterHeaders.forEach((header) => {
        header.addEventListener('click', () => {
            const group = header.parentElement;
            if (group) {
                group.classList.toggle('active');
            }
        });
    });

    const categoryItems = document.querySelectorAll('.category-item');
    categoryItems.forEach((item) => {
        item.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && link.getAttribute('href') && link.getAttribute('href') !== '#' && !link.getAttribute('href').startsWith('javascript:')) {
                // Allow default navigation for real links
                return;
            }
            // Prevent actual navigation for demo/prototype layout
            e.preventDefault();
            categoryItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
        });
    });

    // 8. Hero Slider Logic
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slider-dot');
    const prevBtn = document.querySelector('.prev-slide');
    const nextBtn = document.querySelector('.next-slide');
    let currentSlide = 0;
    let slideInterval;

    if (slides.length > 0) {
        // Apply backgrounds dynamically
        slides.forEach(slide => {
            const bg = slide.getAttribute('data-bg');
            if (bg) {
                slide.style.backgroundImage = `url('${bg}')`;
            }
        });

        function showSlide(index) {
            slides[currentSlide].classList.remove('active');
            dots[currentSlide].classList.remove('active');
            
            currentSlide = (index + slides.length) % slides.length;
            
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        if (nextBtn) nextBtn.addEventListener('click', () => {
            nextSlide();
            resetTimer();
        });
        if (prevBtn) prevBtn.addEventListener('click', () => {
            prevSlide();
            resetTimer();
        });

        dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => {
                showSlide(idx);
                resetTimer();
            });
        });

        function startTimer() {
            slideInterval = setInterval(nextSlide, 6000);
        }

        function resetTimer() {
            clearInterval(slideInterval);
            startTimer();
        }

        startTimer();
    }

    // Fetch and render categories on homepage
    async function loadHomepageCategories() {
        const grid = document.getElementById('homepage-categories-grid');
        if (!grid) return;

        // Render skeleton loader
        grid.innerHTML = Array(4).fill().map(() => `
            <div class="category-skeleton-card">
                <div class="category-skeleton-img"></div>
                <div class="category-skeleton-text-1"></div>
                <div class="category-skeleton-text-2"></div>
            </div>
        `).join('');

        try {
            const response = await fetch('api.php?action=categories');
            const json = await response.json();
            if (json.success && json.data.length > 0) {
                grid.innerHTML = '';
                json.data.forEach(cat => {
                    const card = document.createElement('a');
                    card.href = `products.html?category_id=${cat.id}`;
                    card.className = 'category-card-home';
                    card.innerHTML = `
                        <div class="category-card-img-box">
                            <img src="${escapeHtml(cat.image)}" alt="${escapeHtml(cat.name)}" loading="lazy">
                            <div class="category-card-overlay"></div>
                        </div>
                        <div class="category-card-details">
                            <h3 class="category-card-title">${escapeHtml(cat.name)}</h3>
                            <span class="category-card-cta">
                                View Products
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </span>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            } else {
                grid.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px;">No categories found.</p>`;
            }
        } catch (error) {
            console.error('Error fetching categories:', error);
            grid.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: red; padding: 40px;">Failed to load categories.</p>`;
        }
    }

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    loadHomepageCategories();
});

