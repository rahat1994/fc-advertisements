<?php

if (!defined('ABSPATH')) {
    exit;
}

class FC_Advertisements_Frontend {

    private $db;

    public function __construct($db) {
        $this->db = $db;
        add_action('wp_footer', array($this, 'inject_feed_ads_script'));
        add_action('fluent_community/portal_footer', array($this, 'inject_feed_ads_script'));
    }

    /**
     * Get current space slug from URL
     * Example URL: http://testing_ground.test/portal/space/start-here/home
     * Returns: 'start-here'
     * 
     * NOTE: This is kept for potential future use, but since FluentCommunity is a SPA,
     * space detection should happen in JavaScript for proper navigation handling.
     */
    private function get_current_space_slug() {
        $current_url = $_SERVER['REQUEST_URI'];
        
        // Match pattern: /portal/space/{slug}/
        if (preg_match('#/portal/space/([^/]+)/#', $current_url, $matches)) {
            return $matches[1];
        }
        
        // If not on a space page, return empty string
        return '';
    }

    /**
     * Inject JavaScript to display ads in the feed
     */
    public function inject_feed_ads_script() {
        // Prevent multiple injections
        static $script_injected = false;
        if ($script_injected) {
            return;
        }
        $script_injected = true;
        
        // Fetch ALL enabled ads for content position
        // JavaScript will handle space-specific filtering since FluentCommunity is a SPA
        $feed_ads = $this->db->get_all(array(
            'status' => 'enabled',
            'position' => 'content'
        ));
        
        if (empty($feed_ads)) {
            return;
        }
        
        $ads_data = array_values($feed_ads);
        ?>
        <style>
            .fc-sponsored-post {
                background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
                border: 1px solid #e1e5eb;
                border-radius: 12px;
                padding: 20px;
                margin: 10px 0;
                position: relative;
                transition: all 0.3s ease;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            }
            .fc-sponsored-post:hover {
                box-shadow: 0 4px 16px rgba(0,0,0,0.08);
                transform: translateY(-1px);
            }
            .fc-sponsored-label {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                font-size: 11px;
                color: #6c757d;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 12px;
                font-weight: 600;
            }
            .fc-sponsored-label svg {
                width: 14px;
                height: 14px;
            }
            .fc-sponsored-content {
                display: flex;
                align-items: center;
                gap: 16px;
            }
            .fc-sponsored-icon {
                width: 48px;
                height: 48px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .fc-sponsored-icon svg {
                width: 24px;
                height: 24px;
                color: white;
            }
            .fc-sponsored-info {
                flex: 1;
                min-width: 0;
            }
            .fc-sponsored-title {
                font-size: 16px;
                font-weight: 600;
                color: #1a1a2e;
                margin: 0 0 4px 0;
                line-height: 1.4;
            }
            .fc-sponsored-meta {
                font-size: 13px;
                color: #6c757d;
            }
            .fc-sponsored-cta {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 10px 20px;
                border-radius: 8px;
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.2s ease;
                flex-shrink: 0;
            }
            .fc-sponsored-cta:hover {
                transform: scale(1.02);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                color: white;
                text-decoration: none;
            }
            .fc-sponsored-cta svg {
                width: 16px;
                height: 16px;
            }
        </style>
        <script type="text/javascript">
        (function() {
            // Store ads data
            window.fcAdsData = <?php echo json_encode($ads_data); ?>;
            window.fcAdsObserver = null;
            window.fcAdsLastPath = null;
            
            // Get current space slug from URL
            function getCurrentSpaceSlug() {
                // Always use current window location for accurate space detection
                const url = window.location.pathname;
                
                // Regex handles both /portal/space/{slug}/ and /portal/space/{slug} (no trailing slash)
                const match = url.match(/\/portal\/space\/([^\/]+)(?:\/|$)/);
                return match ? match[1] : '';
            }
            
            // Filter ads by current space
            function getAdsForCurrentSpace() {
                const currentSpace = getCurrentSpaceSlug();
                const allAds = window.fcAdsData || [];
                
                console.log('FC Ads: Current space:', currentSpace || '(none - showing all)');
                
                // If not on a space page (e.g., /portal/), show ads marked for 'all'
                if (!currentSpace) {
                    return allAds.filter(function(ad) {
                        return ad.space === 'all';
                    });
                }
                
                // On a specific space page: show ads for current space OR ads marked for 'all' spaces
                return allAds.filter(function(ad) {
                    return ad.space === 'all' || ad.space === currentSpace;
                });
            }
            
            // Create sponsored post HTML
            function createSponsoredPost(ad) {
                const div = document.createElement('div');
                div.className = 'fc-sponsored-post feed_list_item';
                div.setAttribute('data-ad-id', ad.id);
                div.setAttribute('data-ad-space', ad.space);
                div.innerHTML = `
                    <div class="fc-sponsored-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h2v-2h-2v2zm0-4h2V7h-2v6z"/>
                        </svg>
                        Sponsored
                    </div>
                    <div class="fc-sponsored-content">
                        <div class="fc-sponsored-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14h-2v-4H8v-2h4V7h2v4h4v2h-4v4z"/>
                            </svg>
                        </div>
                        <div class="fc-sponsored-info">
                            <h3 class="fc-sponsored-title">${escapeHtml(ad.title)}</h3>
                            <div class="fc-sponsored-meta">${escapeHtml(ad.position)} placement</div>
                        </div>
                        <a href="${escapeHtml(ad.url)}" target="_blank" rel="noopener noreferrer" class="fc-sponsored-cta">
                            Learn More
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                            </svg>
                        </a>
                    </div>
                `;
                return div;
            }
            
            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Inject ads into feed
            function injectAds() {
                const feedContainer = document.querySelector('.all_feeds_holder');
                const feedItems = document.querySelectorAll('.feed_list_item:not(.fc-sponsored-post)');
                
                if (!feedContainer || feedItems.length === 0) {
                    return false;
                }
                
                // Remove any existing sponsored posts first
                document.querySelectorAll('.fc-sponsored-post').forEach(el => el.remove());
                
                // Get ads for current space
                const ads = getAdsForCurrentSpace();
                
                console.log('FC Ads: Found', ads.length, 'ads for current space');
                
                if (!ads || ads.length === 0) {
                    return true; // No ads to show, but feed exists
                }
                
                // Insert ads after every 2nd post
                let adIndex = 0;
                feedItems.forEach((item, index) => {
                    if ((index + 1) % 2 === 0 && adIndex < ads.length) {
                        const adElement = createSponsoredPost(ads[adIndex]);
                        item.parentNode.insertBefore(adElement, item.nextSibling);
                        adIndex++;
                    }
                });
                
                console.log('FC Ads: Injected', adIndex, 'ads into feed');
                return true;
            }
            
            // Setup MutationObserver for feed changes
            function setupObserver() {
                // Disconnect existing observer if any
                if (window.fcAdsObserver) {
                    window.fcAdsObserver.disconnect();
                    window.fcAdsObserver = null;
                }
                
                let debounceTimer = null;
                let isInjecting = false;
                
                // Watch for dynamic content changes
                window.fcAdsObserver = new MutationObserver((mutations) => {
                    // Ignore mutations while we're injecting ads
                    if (isInjecting) {
                        return;
                    }
                    
                    let shouldReinject = false;
                    
                    for (const mutation of mutations) {
                        if (mutation.addedNodes.length > 0) {
                            for (const node of mutation.addedNodes) {
                                if (node.nodeType === 1) {
                                    // Ignore our own sponsored posts
                                    if (node.classList && node.classList.contains('fc-sponsored-post')) {
                                        continue;
                                    }
                                    
                                    // Trigger on feed items or feed container
                                    if (node.classList && (
                                        node.classList.contains('feed_list_item') ||
                                        node.classList.contains('all_feeds_holder')
                                    )) {
                                        shouldReinject = true;
                                        break;
                                    }
                                    
                                    // Check if node contains feed items
                                    if (node.querySelector && node.querySelector('.feed_list_item:not(.fc-sponsored-post)')) {
                                        shouldReinject = true;
                                        break;
                                    }
                                }
                            }
                        }
                        if (shouldReinject) break;
                    }
                    
                    if (shouldReinject) {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            isInjecting = true;
                            injectAds();
                            setTimeout(() => {
                                isInjecting = false;
                            }, 500);
                        }, 200);
                    }
                });
                
                // Observe the main content area to catch feed container creation
                const mainContent = document.querySelector('.fcom_portal_layout') || 
                                   document.querySelector('.el-main.fcom_main') ||
                                   document.querySelector('#fcom_app') ||
                                   document.body;
                
                if (mainContent) {
                    window.fcAdsObserver.observe(mainContent, {
                        childList: true,
                        subtree: true
                    });
                    console.log('FC Ads: Observer attached to', mainContent.className || 'body');
                }
            }
            
            // Handle navigation (called on route change)
            function handleNavigation() {
                const currentPath = window.location.pathname;
                
                // Check if we actually navigated to a different path
                if (window.fcAdsLastPath === currentPath) {
                    return;
                }
                
                console.log('FC Ads: Navigation detected from', window.fcAdsLastPath, 'to', currentPath);
                window.fcAdsLastPath = currentPath;
                
                // Remove existing ads immediately on navigation
                document.querySelectorAll('.fc-sponsored-post').forEach(el => el.remove());
                
                // Wait for new feed content to load, then inject
                let retryCount = 0;
                const maxRetries = 30; // 6 seconds max
                
                const tryInject = () => {
                    const feedContainer = document.querySelector('.all_feeds_holder');
                    const feedItems = document.querySelectorAll('.feed_list_item:not(.fc-sponsored-post)');
                    
                    if (feedContainer && feedItems.length > 0) {
                        // Feed is ready, inject ads
                        injectAds();
                        // Re-setup observer for the new feed
                        setupObserver();
                    } else if (retryCount < maxRetries) {
                        retryCount++;
                        setTimeout(tryInject, 200);
                    } else {
                        console.log('FC Ads: Feed not found after navigation, giving up');
                    }
                };
                
                // Start trying after a short delay to let Vue update the DOM
                setTimeout(tryInject, 100);
            }
            
            // Initialize the ad system
            function init() {
                console.log('FC Ads: Initializing...');
                
                window.fcAdsLastPath = window.location.pathname;
                
                // Initial injection
                if (injectAds()) {
                    console.log('FC Ads: Initial injection successful');
                }
                
                // Setup observer
                setupObserver();
                
                // Hook into Vue Router if available
                if (window.fluentFrameworkAppRouter) {
                    console.log('FC Ads: Hooking into FluentFramework router');
                    window.fluentFrameworkAppRouter.afterEach((to, from) => {
                        // Use nextTick-like delay to ensure Vue has updated
                        setTimeout(handleNavigation, 50);
                    });
                }
                
                // Also watch for URL changes via popstate (back/forward buttons)
                window.addEventListener('popstate', () => {
                    setTimeout(handleNavigation, 50);
                });
            }
            
            // Wait for router to be available, then initialize
            function waitForRouter() {
                let attempts = 0;
                const maxAttempts = 50; // 5 seconds max
                
                const checkRouter = () => {
                    if (window.fluentFrameworkAppRouter) {
                        init();
                    } else if (attempts < maxAttempts) {
                        attempts++;
                        setTimeout(checkRouter, 100);
                    } else {
                        // Router not found, initialize anyway
                        console.log('FC Ads: Router not found, initializing without router hooks');
                        init();
                    }
                };
                
                checkRouter();
            }
            
            // Start when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', waitForRouter);
            } else {
                waitForRouter();
            }
            
            // Also listen for FluentCommunity ready event
            document.addEventListener('fluentCommunityUtilReady', () => {
                console.log('FC Ads: FluentCommunity ready event received');
                setTimeout(handleNavigation, 100);
            });
        })();
        </script>
        <?php
    }
}
