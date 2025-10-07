/**
 * Cozy Mode Admin JavaScript
 * Enhanced admin interface functionality
 */

(function($) {
	'use strict';

	// Admin interface class
	class CozyModeAdmin {
		constructor() {
			this.init();
		}

		init() {
			this.bindEvents();
			this.setupTooltips();
			this.setupAutoRefresh();
		}

		bindEvents() {
			// Clear cache button
			$(document).on('click', '#clear-cache', (e) => {
				e.preventDefault();
				this.clearCache();
			});

			// Run tests button
			$(document).on('click', '#run-tests', (e) => {
				e.preventDefault();
				this.runTests();
			});

			// Export logs button
			$(document).on('click', '#export-logs', (e) => {
				e.preventDefault();
				this.exportLogs();
			});

			// Clear logs button
			$(document).on('click', '#clear-logs', (e) => {
				e.preventDefault();
				this.clearLogs();
			});

			// Keyboard shortcuts
			$(document).on('keydown', (e) => {
				if (e.ctrlKey || e.metaKey) {
					switch(e.key) {
						case 'r':
							e.preventDefault();
							this.runTests();
							break;
						case 'c':
							e.preventDefault();
							this.clearCache();
							break;
					}
				}
			});
		}

		setupTooltips() {
			// Add tooltips to buttons
			$('#clear-cache').attr('title', 'Clear all cached data (Ctrl+C)');
			$('#run-tests').attr('title', 'Run all tests (Ctrl+R)');
			$('#export-logs').attr('title', 'Download log file');
			$('#clear-logs').attr('title', 'Clear log file');
		}

		setupAutoRefresh() {
			// Auto-refresh every 30 seconds
			setInterval(() => {
				this.refreshStats();
			}, 30000);
		}

		clearCache() {
			const button = $('#clear-cache');
			const originalText = button.text();
			
			this.setButtonLoading(button, true, cozyModeAdmin.strings.loading);

			$.ajax({
				url: cozyModeAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cozy_mode_clear_cache',
					nonce: cozyModeAdmin.nonce
				},
				success: (response) => {
					if (response.success) {
						this.showNotification(response.data.message, 'success');
						this.refreshStats();
					} else {
						this.showNotification(response.data.message || 'Error clearing cache', 'error');
					}
				},
				error: () => {
					this.showNotification('Network error occurred', 'error');
				},
				complete: () => {
					this.setButtonLoading(button, false, originalText);
				}
			});
		}

		runTests() {
			const button = $('#run-tests');
			const originalText = button.text();
			
			this.setButtonLoading(button, true, cozyModeAdmin.strings.loading);

			$.ajax({
				url: cozyModeAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cozy_mode_run_tests',
					nonce: cozyModeAdmin.nonce
				},
				success: (response) => {
					if (response.success) {
						this.showNotification(response.data.message, 'success');
						this.updateTestResults(response.data.results, response.data.summary);
					} else {
						this.showNotification(response.data.message || 'Error running tests', 'error');
					}
				},
				error: () => {
					this.showNotification('Network error occurred', 'error');
				},
				complete: () => {
					this.setButtonLoading(button, false, originalText);
				}
			});
		}

		exportLogs() {
			const button = $('#export-logs');
			const originalText = button.text();
			
			this.setButtonLoading(button, true, cozyModeAdmin.strings.loading);

			// Create a temporary form to trigger download
			const form = $('<form>', {
				method: 'POST',
				action: cozyModeAdmin.ajaxUrl,
				target: '_blank'
			});

			form.append($('<input>', {
				type: 'hidden',
				name: 'action',
				value: 'cozy_mode_export_logs'
			}));

			form.append($('<input>', {
				type: 'hidden',
				name: 'nonce',
				value: cozyModeAdmin.nonce
			}));

			$('body').append(form);
			form.submit();
			form.remove();

			setTimeout(() => {
				this.setButtonLoading(button, false, originalText);
				this.showNotification('Log export initiated', 'success');
			}, 1000);
		}

		clearLogs() {
			if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
				return;
			}

			const button = $('#clear-logs');
			const originalText = button.text();
			
			this.setButtonLoading(button, true, cozyModeAdmin.strings.loading);

			$.ajax({
				url: cozyModeAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cozy_mode_clear_logs',
					nonce: cozyModeAdmin.nonce
				},
				success: (response) => {
					if (response.success) {
						this.showNotification(response.data.message, 'success');
						this.refreshStats();
					} else {
						this.showNotification(response.data.message || 'Error clearing logs', 'error');
					}
				},
				error: () => {
					this.showNotification('Network error occurred', 'error');
				},
				complete: () => {
					this.setButtonLoading(button, false, originalText);
				}
			});
		}

		refreshStats() {
			// Refresh page to get updated stats
			// In a real implementation, you'd make AJAX calls to get updated data
			console.log('Refreshing stats...');
		}

		updateTestResults(results, summary) {
			// Update test summary
			$('.test-summary-item').each(function() {
				const label = $(this).find('.label').text();
				let value = $(this).find('.value');
				
				switch(label) {
					case 'Total Tests:':
						value.text(summary.total);
						break;
					case 'Passed:':
						value.text(summary.passed);
						break;
					case 'Failed:':
						value.text(summary.failed);
						break;
					case 'Success Rate:':
						value.text(Math.round(summary.success_rate * 10) / 10 + '%');
						break;
				}
			});

			// Update detailed test results
			const resultsContainer = $('.cozy-mode-test-results');
			resultsContainer.empty();

			results.forEach(result => {
				const resultDiv = $(`
					<div class="test-result ${result.passed ? 'passed' : 'failed'}">
						<div class="test-name">
							${result.name}
							<span class="test-status">${result.passed ? '✓' : '✗'}</span>
						</div>
						<div class="test-message">${result.message}</div>
						<div class="test-timestamp">${result.timestamp}</div>
					</div>
				`);
				resultsContainer.append(resultDiv);
			});
		}

		setButtonLoading(button, loading, text = null) {
			if (loading) {
				button.addClass('cozy-mode-loading');
				button.prop('disabled', true);
				if (text) {
					button.data('original-text', button.text());
					button.text(text);
				}
			} else {
				button.removeClass('cozy-mode-loading');
				button.prop('disabled', false);
				if (text) {
					button.text(text);
				} else if (button.data('original-text')) {
					button.text(button.data('original-text'));
				}
			}
		}

		showNotification(message, type = 'info') {
			// Remove existing notifications
			$('.cozy-mode-notification').remove();

			// Create notification
			const notification = $(`
				<div class="cozy-mode-notification cozy-mode-notification-${type}">
					<span class="notification-message">${message}</span>
					<button type="button" class="notification-close">&times;</button>
				</div>
			`);

			// Add to page
			$('.wrap h1').after(notification);

			// Auto-hide after 5 seconds
			setTimeout(() => {
				notification.fadeOut(() => {
					notification.remove();
				});
			}, 5000);

			// Close button
			notification.find('.notification-close').on('click', () => {
				notification.fadeOut(() => {
					notification.remove();
				});
			});
		}
	}

	// Initialize admin interface when document is ready
	$(document).ready(() => {
		new CozyModeAdmin();
	});

})(jQuery);
