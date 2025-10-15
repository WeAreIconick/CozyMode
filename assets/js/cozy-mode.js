/**
 * Cozy Mode JavaScript - Enhanced Version
 * Integrates Readability.js for content extraction with advanced features
 */

(function() {
	'use strict';

	// Check if Readability is available
	if (typeof Readability === 'undefined') {
		console.error('Cozy Mode: Readability.js is not loaded');
		return;
	}

	// Check if cozyMode object is available
	if (typeof cozyMode === 'undefined') {
		console.error('Cozy Mode: Localized data is not available');
		return;
	}

	// Main Cozy Mode class
	class CozyMode {
		constructor() {
			this.modal = null;
			this.backdrop = null;
			this.container = null;
			this.title = null;
			this.content = null;
			this.loading = null;
			this.closeButton = null;
			this.toggleButton = null;
			this.controls = null;
			this.isActive = false;
			this.focusableElements = [];
			this.firstFocusableElement = null;
			this.lastFocusableElement = null;
			this.previousActiveElement = null;
			this.currentFontSize = 18;
			this.isDarkMode = false;
			this.retryCount = 0;
			this.maxRetries = 3;
			
			this.init();
		}

		init() {
			this.loadPreferences();
			this.bindEvents();
			this.setupElements();
			this.setupPerformanceMonitoring();
		}

		setupPerformanceMonitoring() {
			// Monitor performance metrics
			if ('performance' in window) {
				this.performanceStart = performance.now();
			}
		}

		loadPreferences() {
			try {
				const preferences = localStorage.getItem('cozyModePreferences');
				if (preferences) {
					const prefs = JSON.parse(preferences);
					this.currentFontSize = prefs.fontSize || 18;
					this.isDarkMode = prefs.darkMode || false;
				}
			} catch (error) {
				console.warn('Cozy Mode: Could not load preferences:', error);
			}
		}

		savePreferences() {
			try {
				const preferences = {
					fontSize: this.currentFontSize,
					darkMode: this.isDarkMode,
					lastUsed: new Date().toISOString(),
					version: cozyMode.version || '1.0.0'
				};
				localStorage.setItem('cozyModePreferences', JSON.stringify(preferences));
			} catch (error) {
				console.warn('Cozy Mode: Could not save preferences:', error);
			}
		}

		bindEvents() {
			// Toggle button click with enhanced error handling
			document.addEventListener('click', (e) => {
				if (e.target.closest('.cozy-mode-toggle')) {
					e.preventDefault();
					this.handleToggleClick(e);
				}
			});

			// Close button click
			document.addEventListener('click', (e) => {
				if (e.target.closest('.cozy-mode-close')) {
					e.preventDefault();
					this.closeModal();
				}
			});

			// Click overlay - handle clicks outside modal content
			document.addEventListener('click', (e) => {
				if (!this.isActive || !this.modal) return;
				
				// Don't close if clicking on the toggle button
				if (e.target.closest('.cozy-mode-toggle')) {
					return;
				}
				
				// Check if click is on the click overlay (outside content)
				if (e.target.classList.contains('cozy-mode-click-overlay')) {
					this.closeModal();
				}
			});

			// Escape key
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && this.isActive) {
					this.closeModal();
				}
			});

			// Control buttons with enhanced functionality
			document.addEventListener('click', (e) => {
				const target = e.target.closest('.cozy-mode-control');
				if (!target) return;

				e.preventDefault();

				if (target.classList.contains('cozy-mode-font-decrease')) {
					this.decreaseFontSize();
				} else if (target.classList.contains('cozy-mode-font-reset')) {
					this.resetFontSize();
				} else if (target.classList.contains('cozy-mode-font-increase')) {
					this.increaseFontSize();
				} else if (target.classList.contains('cozy-mode-theme-toggle')) {
					this.toggleTheme();
				} else if (target.classList.contains('cozy-mode-print')) {
					this.printArticle();
				}
			});

			// Keyboard navigation for controls
			document.addEventListener('keydown', (e) => {
				if (!this.isActive) return;

				if (e.key === 'Tab') {
					this.handleTabKey(e);
				}
			});

			// Handle window resize for responsive adjustments
			window.addEventListener('resize', this.debounce(() => {
				this.handleResize();
			}, 250));
		}

		handleToggleClick(e) {
			const button = e.target.closest('.cozy-mode-toggle');
			const postId = button?.dataset.postId;
			
			if (!postId) {
				console.error('Cozy Mode: Post ID not found');
				return;
			}

			// Add loading state to button
			this.setButtonLoading(button, true);

			// Open modal with retry logic
			this.openModalWithRetry();
		}

		setButtonLoading(button, loading) {
			if (loading) {
				button.disabled = true;
				button.classList.add('loading');
				const icon = button.querySelector('.cozy-mode-icon');
				if (icon) {
					icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
				}
			} else {
				button.disabled = false;
				button.classList.remove('loading');
				const icon = button.querySelector('.cozy-mode-icon');
				if (icon) {
					icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6H20M4 12H20M4 18H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
				}
			}
		}

		async openModalWithRetry() {
			try {
				await this.openModal();
				this.retryCount = 0; // Reset on success
			} catch (error) {
				this.retryCount++;
				if (this.retryCount < this.maxRetries) {
					console.warn(`Cozy Mode: Retry ${this.retryCount}/${this.maxRetries}`);
					setTimeout(() => this.openModalWithRetry(), 1000 * this.retryCount);
				} else {
					console.error('Cozy Mode: Max retries exceeded');
					this.showError('Maximum retry attempts exceeded. Please refresh the page.');
				}
			}
		}

		setupElements() {
			this.modal = document.getElementById('cozy-mode-modal');
			this.backdrop = this.modal?.querySelector('.cozy-mode-backdrop');
			this.container = this.modal?.querySelector('.cozy-mode-container');
			this.content = this.modal?.querySelector('.cozy-mode-article');
			this.loading = this.modal?.querySelector('.cozy-mode-loading');
			this.closeButton = this.modal?.querySelector('.cozy-mode-close');
			this.controls = this.modal?.querySelector('.cozy-mode-controls');

			if (!this.modal || !this.content) {
				console.error('Cozy Mode: Required elements not found');
				return;
			}

			this.updateFocusableElements();
		}

		debounce(func, wait) {
			let timeout;
			return function executedFunction(...args) {
				const later = () => {
					clearTimeout(timeout);
					func(...args);
				};
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
			};
		}

		handleResize() {
			if (this.isActive) {
				this.updateFocusableElements();
			}
		}

		updateFocusableElements() {
			if (!this.modal) return;

			const focusableSelectors = [
				'button:not([disabled])',
				'[href]',
				'input:not([disabled])',
				'select:not([disabled])',
				'textarea:not([disabled])',
				'[tabindex]:not([tabindex="-1"])'
			];

			this.focusableElements = Array.from(
				this.modal.querySelectorAll(focusableSelectors.join(', '))
			);

			this.firstFocusableElement = this.focusableElements[0];
			this.lastFocusableElement = this.focusableElements[this.focusableElements.length - 1];
		}

		handleTabKey(e) {
			if (!this.isActive) return;

			if (e.shiftKey) {
				// Shift + Tab
				if (document.activeElement === this.firstFocusableElement) {
					e.preventDefault();
					this.lastFocusableElement?.focus();
				}
			} else {
				// Tab
				if (document.activeElement === this.lastFocusableElement) {
					e.preventDefault();
					this.firstFocusableElement?.focus();
				}
			}
		}

		async openModal() {
			try {
				// Store the currently focused element
				this.previousActiveElement = document.activeElement;

				// Show modal with loading state
				this.showModal();
				this.showLoadingState();

				// Extract content using Readability.js with timeout
				const article = await Promise.race([
					this.extractContent(),
					this.timeoutPromise(10000) // 10 second timeout
				]);
				
				if (!article) {
					throw new Error('Content extraction failed or timed out');
				}

				// Populate modal content
				this.populateModal(article);

				// Hide loading state
				this.hideLoadingState();

				// Update focusable elements
				this.updateFocusableElements();

				// Focus first element
				this.firstFocusableElement?.focus();

				// Log performance metrics
				this.logPerformanceMetrics();

				// Reset button loading state
				const buttons = document.querySelectorAll('.cozy-mode-toggle');
				buttons.forEach(button => this.setButtonLoading(button, false));

			} catch (error) {
				console.error('Cozy Mode: Error opening modal:', error);
				this.hideLoadingState();
				this.showError(error.message || 'An error occurred while loading content');
				
				// Reset button loading state
				const buttons = document.querySelectorAll('.cozy-mode-toggle');
				buttons.forEach(button => this.setButtonLoading(button, false));
				
				throw error; // Re-throw for retry logic
			}
		}

		timeoutPromise(ms) {
			return new Promise((_, reject) => {
				setTimeout(() => reject(new Error('Operation timed out')), ms);
			});
		}

		showLoadingState() {
			if (this.loading) {
				this.loading.style.display = 'block';
			}
			if (this.content) {
				this.content.style.display = 'none';
			}
		}

		hideLoadingState() {
			if (this.loading) {
				this.loading.style.display = 'none';
			}
			if (this.content) {
				this.content.style.display = 'block';
			}
		}

		logPerformanceMetrics() {
			if ('performance' in window && this.performanceStart) {
				const loadTime = performance.now() - this.performanceStart;
				console.log(`Cozy Mode: Modal loaded in ${loadTime.toFixed(2)}ms`);
				
				// Send metrics to analytics if available
				if (typeof gtag !== 'undefined') {
					gtag('event', 'cozy_mode_load_time', {
						'value': Math.round(loadTime),
						'event_category': 'performance'
					});
				}
			}
		}

		async extractContent() {
			try {
				// Clone the current document
				const documentClone = document.cloneNode(true);
				
				// Remove the cozy mode button from the clone to prevent duplication
				const cozyButtons = documentClone.querySelectorAll('.cozy-mode-button-container');
				cozyButtons.forEach(button => button.remove());
				
				// Create Readability instance
				const reader = new Readability(documentClone);
				
				// Parse the content
				const article = reader.parse();
				
				if (!article) {
					throw new Error('Readability.js could not parse content');
				}

				return article;
			} catch (error) {
				console.error('Cozy Mode: Content extraction failed:', error);
				return null;
			}
		}

		populateModal(article) {
			if (!this.content) return;

			// Set content safely - use textContent for security
			// Note: Readability.js already sanitizes content, but we'll be extra safe
			if (article.content) {
				// Create a temporary div to parse the HTML safely
				const tempDiv = document.createElement('div');
				tempDiv.innerHTML = article.content;
				
				// Clear existing content
				this.content.innerHTML = '';
				
				// Append sanitized content
				while (tempDiv.firstChild) {
					this.content.appendChild(tempDiv.firstChild);
				}
			} else {
				this.content.innerHTML = '';
			}

			// Apply current preferences
			this.applyPreferences();
			
			// Remove top margin from first heading
			this.removeFirstHeadingTopMargin();
		}

		showModal() {
			if (!this.modal) return;

			// Add active class to body
			document.body.classList.add('cozy-mode-active');

			// Show modal
			this.modal.removeAttribute('hidden');
			this.modal.classList.add('active');

			this.isActive = true;
		}

		closeModal() {
			if (!this.modal || !this.isActive) return;

			// Hide modal
			this.modal.classList.remove('active');
			this.modal.setAttribute('hidden', '');

			// Remove active class from body
			document.body.classList.remove('cozy-mode-active');

			// Return focus to the element that opened the modal
			if (this.previousActiveElement) {
				this.previousActiveElement.focus();
			}

			this.isActive = false;
		}

		showError(message = null) {
			if (!this.content) return;
			
			// Create error message safely
			const errorDiv = document.createElement('div');
			errorDiv.className = 'cozy-mode-error';
			
			const errorP = document.createElement('p');
			errorP.textContent = message || cozyMode.strings.extractionError;
			errorDiv.appendChild(errorP);
			
			// Add retry button
			const retryButton = document.createElement('button');
			retryButton.textContent = 'Retry';
			retryButton.className = 'cozy-mode-retry-button';
			retryButton.addEventListener('click', () => {
				this.openModalWithRetry();
			});
			errorDiv.appendChild(retryButton);
			
			// Get fallback content safely
			const fallbackElement = document.querySelector('.entry-content') || document.body;
			if (fallbackElement) {
				const fallbackP = document.createElement('p');
				fallbackP.textContent = fallbackElement.textContent || 'Content not available';
				errorDiv.appendChild(fallbackP);
			}
			
			// Clear and set content safely
			this.content.innerHTML = '';
			this.content.appendChild(errorDiv);
		}

		printArticle() {
			if (!this.content) return;

			// Create print-friendly version
			const printWindow = window.open('', '_blank');
			const printContent = `
				<!DOCTYPE html>
				<html>
				<head>
					<title>Article</title>
					<style>
						body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; max-width: 650px; margin: 0 auto; padding: 20px; }
						h1, h2, h3 { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
						@media print { body { margin: 0; padding: 0; } }
					</style>
				</head>
				<body>
					${this.content.innerHTML}
				</body>
				</html>
			`;

			printWindow.document.write(printContent);
			printWindow.document.close();
			printWindow.print();
		}

		decreaseFontSize() {
			this.currentFontSize = Math.max(14, this.currentFontSize - 2);
			this.applyFontSize();
			this.savePreferences();
		}

		increaseFontSize() {
			this.currentFontSize = Math.min(24, this.currentFontSize + 2);
			this.applyFontSize();
			this.savePreferences();
		}

		resetFontSize() {
			this.currentFontSize = 18;
			this.applyFontSize();
			this.savePreferences();
		}

		applyFontSize() {
			if (!this.content) return;

			this.content.style.fontSize = this.currentFontSize + 'px';
			
			// Update CSS custom property
			document.documentElement.style.setProperty('--cozy-font-size', this.currentFontSize + 'px');
		}

		toggleTheme() {
			this.isDarkMode = !this.isDarkMode;
			this.applyTheme();
			this.savePreferences();
		}

		applyTheme() {
			if (!this.modal) return;

			if (this.isDarkMode) {
				this.modal.classList.add('cozy-mode-dark');
			} else {
				this.modal.classList.remove('cozy-mode-dark');
			}
		}

		applyPreferences() {
			this.applyFontSize();
			this.applyTheme();
		}

		removeFirstHeadingTopMargin() {
			if (!this.content) return;
			
			// Find the first heading (h1, h2, h3, h4, h5, or h6)
			const firstHeading = this.content.querySelector('h1, h2, h3, h4, h5, h6');
			if (firstHeading) {
				firstHeading.classList.add('first-heading');
			}
		}
	}

	// Initialize when DOM is ready
	function initCozyMode() {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', () => {
				new CozyMode();
			});
		} else {
			new CozyMode();
		}
	}

	// Initialize
	initCozyMode();

})();
