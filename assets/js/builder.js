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
		try {
		if (window.diviApex27PropertyFilterRegistered) {
			return true;
		}

		const divi = window.divi || window.vendor?.divi || window.parent?.divi;
		const React = window.React || window.parent?.React;

		if (!divi || !divi.moduleLibrary || !React) {
			return false;
		}

		const metadata = window.diviApex27PropertyFilterMetadata || {
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
					try {
						const currentDivi = window.divi || window.vendor?.divi || window.parent?.divi;
						const serverRenderedModule = currentDivi?.module?.ServerRenderedModule ||
							currentDivi?.module?.server?.ServerRenderedModule ||
							currentDivi?.moduleLibrary?.ServerRenderedModule ||
							findComponent(currentDivi?.module, 'ServerRenderedModule') ||
							findComponent(currentDivi?.moduleLibrary, 'ServerRenderedModule');

						if (!serverRenderedModule) {
							return React.createElement('div', { className: 'divi-apex27-builder-placeholder' }, 'Loading Apex27 property results...');
						}

						return React.createElement(serverRenderedModule, props);
					} catch (error) {
						return React.createElement('div', { className: 'divi-apex27-builder-placeholder' }, 'Apex27 preview error. Save and reload builder.');
					}
				}
			}
		});

		window.diviApex27PropertyFilterRegistered = true;
		return true;
		} catch (error) {
			return false;
		}
	}

	if (hooks) {
		hooks.addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'divi-apex27/property-filter', registerModule);
	}

	setTimeout(registerModule, 1500);
})();
