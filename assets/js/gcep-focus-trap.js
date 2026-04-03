/**
 * Utilitário de focus trap e Escape para modais/menus
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 2.1.0 - 2026-03-29
 */
(function () {
	'use strict';

	window.gcepFocusTrap = {
		active: null,

		activate: function (container, onClose) {
			if (!container) return;
			this.active = { container: container, onClose: onClose };

			var focusable = container.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			);
			this.active.focusable = focusable;
			this.active.first = focusable[0] || null;
			this.active.last = focusable[focusable.length - 1] || null;

			container.addEventListener('keydown', this._handleKeydown);
			if (this.active.first) this.active.first.focus();
		},

		deactivate: function () {
			if (this.active && this.active.container) {
				this.active.container.removeEventListener('keydown', this._handleKeydown);
			}
			this.active = null;
		},

		_handleKeydown: function (e) {
			var trap = window.gcepFocusTrap.active;
			if (!trap) return;

			if (e.key === 'Escape') {
				e.preventDefault();
				if (trap.onClose) trap.onClose();
				window.gcepFocusTrap.deactivate();
				return;
			}

			if (e.key === 'Tab') {
				if (!trap.first || !trap.last) return;
				if (e.shiftKey) {
					if (document.activeElement === trap.first) {
						e.preventDefault();
						trap.last.focus();
					}
				} else {
					if (document.activeElement === trap.last) {
						e.preventDefault();
						trap.first.focus();
					}
				}
			}
		}
	};
})();
