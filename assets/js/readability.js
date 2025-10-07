/**
 * Readability.js - Local version for Cozy Mode
 * Simplified version of Mozilla's Readability.js for content extraction
 * Based on Mozilla's Readability.js v0.4.4
 */

(function() {
	'use strict';

	// Simplified Readability class
	class Readability {
		constructor(doc) {
			this.doc = doc;
			this.article = null;
		}

		parse() {
			try {
				// Find the main content area
				const contentSelectors = [
					'article',
					'.entry-content',
					'.post-content',
					'.content',
					'#content',
					'main',
					'.main-content',
					'.article-content'
				];

				let contentElement = null;
				for (const selector of contentSelectors) {
					contentElement = this.doc.querySelector(selector);
					if (contentElement) break;
				}

				// Fallback to body if no content found
				if (!contentElement) {
					contentElement = this.doc.body;
				}

				// Get title
				const title = this.getTitle();

				// Clean and extract content
				const content = this.cleanContent(contentElement);

				return {
					title: title,
					content: content,
					textContent: contentElement ? contentElement.textContent : '',
					length: contentElement ? contentElement.textContent.length : 0
				};
			} catch (error) {
				console.error('Readability parsing error:', error);
				return null;
			}
		}

		getTitle() {
			const titleSelectors = [
				'h1',
				'.entry-title',
				'.post-title',
				'.page-title',
				'title'
			];

			for (const selector of titleSelectors) {
				const element = this.doc.querySelector(selector);
				if (element && element.textContent.trim()) {
					return element.textContent.trim();
				}
			}

			return this.doc.title || 'Untitled';
		}

		cleanContent(element) {
			if (!element) return '';

			// Clone the element to avoid modifying the original
			const clonedElement = element.cloneNode(true);

			// Remove unwanted elements
			const unwantedSelectors = [
				'nav',
				'header',
				'footer',
				'.navigation',
				'.nav',
				'.menu',
				'.sidebar',
				'.widget',
				'.advertisement',
				'.ad',
				'.social-share',
				'.comments',
				'.comment',
				'noscript',
				'script',
				'style',
				'iframe',
				'object',
				'embed'
			];

			unwantedSelectors.forEach(selector => {
				const elements = clonedElement.querySelectorAll(selector);
				elements.forEach(el => el.remove());
			});

			// Clean up attributes
			this.cleanAttributes(clonedElement);

			return clonedElement.innerHTML;
		}

		cleanAttributes(element) {
			// Remove potentially problematic attributes
			const attributesToRemove = [
				'onclick',
				'onload',
				'onerror',
				'onmouseover',
				'onmouseout',
				'onfocus',
				'onblur',
				'onchange',
				'onsubmit'
			];

			const allElements = element.querySelectorAll('*');
			allElements.forEach(el => {
				attributesToRemove.forEach(attr => {
					if (el.hasAttribute(attr)) {
						el.removeAttribute(attr);
					}
				});
			});
		}
	}

	// Export to global scope
	window.Readability = Readability;

})();

