(function() {
	'use strict';

	const hooks = window.vendor?.wp?.hooks || window.wp?.hooks;

	function findComponent(obj, target, depth = 0) {
		if (!obj || depth > 4) {
			return null;
		}

		if (obj[target]) {
			return obj[target];
		}

		for (const key in obj) {
			if (typeof obj[key] === 'object' && obj[key] !== null) {
				const found = findComponent(obj[key], target, depth + 1);
				if (found) {
					return found;
				}
			}
		}

		return null;
	}

	function registerModule() {
		if (window.diviApex27PropertyFilterRegistered) {
			return true;
		}

		const divi = window.divi || window.vendor?.divi || window.parent?.divi;
		const React = window.React || window.parent?.React;

		if (!divi || !divi.moduleLibrary || !React) {
			return false;
		}

		const metadata = window.diviApex27PropertyFilterMetadata || window.diviApex27PropertySearchMetadata || {
			name: 'divi-apex27/property-filter',
			visualBuilderScript: 'divi-apex27-builder',
			title: 'Apex27 Property Filter',
			titles: 'Apex27 Property Filters',
			moduleIcon: 'divi/module-search',
			category: 'module'
		};

		divi.moduleLibrary.registerModule(metadata, {
			renderers: {
				edit: function(props) {
					const currentDivi = window.divi || window.vendor?.divi || window.parent?.divi;
					const serverRenderedModule = findComponent(currentDivi?.module, 'ServerRenderedModule') ||
						findComponent(currentDivi?.moduleLibrary, 'ServerRenderedModule');

					if (!serverRenderedModule) {
						return React.createElement('div', { className: 'divi-apex27-builder-placeholder' }, 'Loading Apex27 Property Filter...');
					}

					return React.createElement(serverRenderedModule, props);
				}
			}
		});

		window.diviApex27PropertyFilterRegistered = true;
		return true;
	}

	if (hooks) {
		hooks.addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'divi-apex27/property-filter', registerModule);
	}

	setTimeout(registerModule, 1500);
})();
