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
     * Inject JavaScript to display ads in the feed
     */
    public function inject_feed_ads_script() {
        // Only run on pages that might have FluentCommunity
        $ads = $this->db->get_all();
        $feed_ads = array_filter($ads, function($ad) {
            return $ad->space === 'content';
        });
        
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
            window.fcAdsInjected = false;
            window.fcAdsInterval = null;
            
            // Create sponsored post HTML
            function createSponsoredPost(ad) {
                const div = document.createElement('div');
                div.className = 'fc-sponsored-post feed_list_item';
                div.setAttribute('data-ad-id', ad.id);
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
                
                const ads = window.fcAdsData;
                if (!ads || ads.length === 0) {
                    return true;
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
                
                return true;
            }
            
            // Watch for feed changes using MutationObserver
            function watchFeed() {
                // Initial injection attempt
                if (injectAds()) {
                    console.log('FC Ads: Injected ads into feed');
                }
                
                // Watch for dynamic content changes
                const observer = new MutationObserver((mutations) => {
                    let shouldReinject = false;
                    
                    mutations.forEach((mutation) => {
                        if (mutation.addedNodes.length > 0) {
                            mutation.addedNodes.forEach((node) => {
                                if (node.nodeType === 1) {
                                    if (node.classList && (
                                        node.classList.contains('feed_list_item') ||
                                        node.classList.contains('all_feeds_holder') ||
                                        node.querySelector && node.querySelector('.feed_list_item')
                                    )) {
                                        shouldReinject = true;
                                    }
                                }
                            });
                        }
                    });
                    
                    if (shouldReinject) {
                        setTimeout(injectAds, 100);
                    }
                });
                
                // Observe the main content area
                const mainContent = document.querySelector('.el-main.fcom_main') || document.body;
                observer.observe(mainContent, {
                    childList: true,
                    subtree: true
                });
            }
            
            // Initialize when DOM is ready
            function init() {
                // Try to inject immediately
                if (document.querySelector('.all_feeds_holder')) {
                    watchFeed();
                } else {
                    // Wait for feed to load (SPA navigation)
                    window.fcAdsInterval = setInterval(() => {
                        if (document.querySelector('.all_feeds_holder')) {
                            clearInterval(window.fcAdsInterval);
                            watchFeed();
                        }
                    }, 500);
                    
                    // Clear interval after 30 seconds to prevent infinite loop
                    setTimeout(() => {
                        if (window.fcAdsInterval) {
                            clearInterval(window.fcAdsInterval);
                        }
                    }, 30000);
                }
            }
            
            // Handle SPA navigation
            if (window.fluentFrameworkAppRouter) {
                window.fluentFrameworkAppRouter.afterEach(() => {
                    setTimeout(init, 300);
                });
            }
            
            // Start initialization
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
            
            // Also listen for the FluentCommunity ready event
            document.addEventListener('fluentCommunityUtilReady', init);
        })();
        </script>
        <?php
    }
}
