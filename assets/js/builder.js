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

	function getDivi() {
		return window.divi || window.vendor?.divi || window.parent?.divi || null;
	}

	function getServerRenderedModule() {
		const currentDivi = getDivi();
		return currentDivi?.module?.ServerRenderedModule || null;
	}

	function getSettingValue(settings, key, fallbackValue) {
		return settings?.[key]?.desktop?.value ?? fallbackValue;
	}

	function toPositiveInt(value, fallbackValue) {
		const parsed = parseInt(value, 10);
		if (Number.isNaN(parsed) || parsed < 1) {
			return fallbackValue;
		}

		return parsed;
	}

	function createPropertyFilterPreviewRenderer(React) {
		const samples = [
			{ title: '2 Bed Apartment, Romford', price: 'GBP 325,000', subtitle: 'Apartment | Romford', beds: '2 Beds', baths: '1 Bath', status: 'For Sale', blurb: 'Bright two-bedroom apartment with open-plan kitchen and balcony.' },
			{ title: '3 Bed House, Barking', price: 'GBP 450,000', subtitle: 'House | Barking', beds: '3 Beds', baths: '2 Baths', status: 'For Sale', blurb: 'Family home with private garden and off-street parking.' },
			{ title: '1 Bed Flat, Ilford', price: 'GBP 1,650 pcm', subtitle: 'Flat | Ilford', beds: '1 Bed', baths: '1 Bath', status: 'To Let', blurb: 'Modern one-bedroom flat near transport and local amenities.' },
			{ title: '4 Bed Detached, Chigwell', price: 'GBP 895,000', subtitle: 'Detached House | Chigwell', beds: '4 Beds', baths: '3 Baths', status: 'For Sale', blurb: 'Spacious detached property in a quiet residential setting.' },
			{ title: '2 Bed Maisonette, London', price: 'GBP 2,200 pcm', subtitle: 'Maisonette | London', beds: '2 Beds', baths: '1 Bath', status: 'To Let', blurb: 'Well-presented maisonette with generous living space.' },
			{ title: '3 Bed End Terrace, Dagenham', price: 'GBP 395,000', subtitle: 'End Terraced House | Dagenham', beds: '3 Beds', baths: '2 Baths', status: 'For Sale', blurb: 'Extended end-terrace with large rear garden.' },
			{ title: 'Studio Flat, Shoreditch', price: 'GBP 1,450 pcm', subtitle: 'Studio | Shoreditch', beds: 'Studio', baths: '1 Bath', status: 'To Let', blurb: 'Compact studio ideal for city living and easy commuting.' },
			{ title: 'Commercial Unit, Hounslow', price: 'GBP 42,000 pa', subtitle: 'Commercial | Hounslow', beds: 'N/A', baths: 'N/A', status: 'Commercial', blurb: 'Versatile ground-floor commercial unit on busy high street.' }
		];

		const createCard = (item, index) => React.createElement(
			'article',
			{ className: 'divi-apex27-card', key: `apex27-mock-${index}` },
			React.createElement(
				'div',
				{ className: 'divi-apex27-card-media' },
				React.createElement('span', { className: 'divi-apex27-card-overlay' }, item.status)
			),
			React.createElement(
				'div',
				{ className: 'divi-apex27-card-body' },
				React.createElement('h3', null, item.title),
				React.createElement('p', { className: 'divi-apex27-card-price' }, item.price),
				React.createElement('p', { className: 'divi-apex27-card-subtitle' }, item.subtitle),
				React.createElement(
					'div',
					{ className: 'divi-apex27-card-meta' },
					React.createElement('span', null, item.beds),
					React.createElement('span', null, item.baths)
				),
				React.createElement('p', { className: 'divi-apex27-card-summary' }, item.blurb),
				React.createElement('span', { className: 'divi-apex27-card-link' }, 'View Details')
			)
		);

		return function edit(props) {
			const settings = props?.attrs?.apex27?.content || {};
			const title = getSettingValue(settings, 'title', 'Property Results');
			const listingType = getSettingValue(settings, 'listing_type', 'listings');
			const type = getSettingValue(settings, 'type', 'rent');
			const columns = Math.min(6, Math.max(1, toPositiveInt(getSettingValue(settings, 'column_count', '4'), 4)));
			const rows = Math.min(6, Math.max(1, toPositiveInt(getSettingValue(settings, 'row_count', '2'), 2)));
			const visibleCount = Math.min(samples.length, columns * rows);
			const cards = samples.slice(0, visibleCount).map(createCard);

			const contextText = `${listingType === 'valuations' ? 'Valuations' : 'Listings'} | ${type}`;

			return React.createElement(
				'div',
				{ className: 'divi-apex27-property-filter divi-apex27-builder-mode' },
				title ? React.createElement('h2', { className: 'divi-apex27-title' }, title) : null,
				React.createElement('p', { className: 'divi-apex27-debug' }, `Builder Preview: ${contextText}`),
				React.createElement(
					'div',
					{ className: 'divi-apex27-results', style: { '--apex27-columns': String(columns) } },
					cards
				)
			);
		};
	}

	function createSearchFormPreviewRenderer(React) {
		const createField = (label, placeholder, key) => React.createElement(
			'label',
			{ className: 'divi-apex27-search-field', key },
			React.createElement(
				'select',
				{ disabled: true, defaultValue: '' },
				React.createElement('option', { value: '' }, placeholder || label)
			)
		);

		return function edit(props) {
			const settings = props?.attrs?.apex27?.content || {};
			const title = getSettingValue(settings, 'title', 'Property Search');
			const submitLabel = getSettingValue(settings, 'submit_label', 'Update');
			const showListingType = getSettingValue(settings, 'show_listing_type', 'off') === 'on';
			const showType = getSettingValue(settings, 'show_type', 'on') === 'on';
			const showPropertyType = getSettingValue(settings, 'show_property_type', 'on') === 'on';
			const showCity = getSettingValue(settings, 'show_city', 'on') === 'on';
			const showMinPrice = getSettingValue(settings, 'show_min_price', 'on') === 'on';
			const showMaxPrice = getSettingValue(settings, 'show_max_price', 'on') === 'on';
			const showMinBeds = getSettingValue(settings, 'show_min_beds', 'on') === 'on';
			const showMaxBeds = getSettingValue(settings, 'show_max_beds', 'on') === 'on';
			const showMinGrossYield = getSettingValue(settings, 'show_min_gross_yield', 'on') === 'on';
			const showSort = getSettingValue(settings, 'show_sort', 'on') === 'on';

			const fields = [];

			if (showListingType) {
				fields.push(createField('Listing Type', 'Listings', 'listing_type'));
			}

			if (showType) {
				fields.push(createField('Type', 'Lettings (Residential)', 'type'));
			}

			if (showPropertyType) {
				fields.push(createField('Property Type', 'Any property type', 'property_type'));
			}

			if (showCity) {
				fields.push(createField('Location', 'Any location', 'city'));
			}

			if (showMinPrice) {
				fields.push(createField('Min Price', 'No minimum', 'min_price'));
			}

			if (showMaxPrice) {
				fields.push(createField('Max Price', 'No maximum', 'max_price'));
			}

			if (showMinBeds) {
				fields.push(createField('Min Bedrooms', 'Any', 'min_beds'));
			}

			if (showMaxBeds) {
				fields.push(createField('Max Bedrooms', 'Any', 'max_beds'));
			}

			if (showMinGrossYield) {
				fields.push(createField('Min Gross Yield', 'Any yield', 'min_gross_yield'));
			}

			if (showSort) {
				fields.push(createField('Sort', 'Highest price', 'sort'));
			}

			if (fields.length === 0) {
				fields.push(
					React.createElement(
						'div',
						{ className: 'divi-apex27-notice', key: 'empty' },
						'All search fields are currently hidden for this module.'
					)
				);
			}

			return React.createElement(
				'div',
				{ className: 'divi-apex27-search-form divi-apex27-builder-mode' },
				title ? React.createElement('h2', { className: 'divi-apex27-search-title' }, title) : null,
				React.createElement(
					'form',
					{ className: 'divi-apex27-search-grid' },
					fields,
					React.createElement(
						'div',
						{ className: 'divi-apex27-search-actions', key: 'actions' },
						React.createElement(
							'button',
							{ type: 'button', className: 'divi-apex27-search-submit' },
							submitLabel || 'Update'
						)
					)
				)
			);
		};
	}

	function createEditRenderer(React, loadingText) {
		return function edit(props) {
			try {
				const serverRenderedModule = getServerRenderedModule();

				if (!serverRenderedModule) {
					window.setTimeout(registerModules, 300);
					return React.createElement('div', { className: 'divi-apex27-builder-placeholder' }, loadingText);
				}

				return React.createElement(serverRenderedModule, props);
			} catch (error) {
				return React.createElement('div', { className: 'divi-apex27-builder-placeholder' }, 'Apex27 preview error. Save and reload builder.');
			}
		};
	}

	function registerModule(definition) {
		try {
		if (!definition || !definition.flag || !definition.metadata) {
			return false;
		}

		if (!definition.metadata.attributes) {
			return false;
		}

		if (window[definition.flag]) {
			return true;
		}

		const divi = getDivi();
		const React = window.React || window.parent?.React;

		if (!divi || !divi.moduleLibrary || !React) {
			return false;
		}

		const metadata = definition.metadata;
		const editRenderer = definition.createRenderer
			? definition.createRenderer(React)
			: createEditRenderer(React, definition.loadingText || 'Loading Apex27 preview...');

		divi.moduleLibrary.registerModule(metadata, {
			renderers: {
				edit: editRenderer
			}
		});

		window[definition.flag] = true;
		return true;
		} catch (error) {
			return false;
		}
	}

	function registerModules() {
		const definitions = [
			{
				flag: 'diviApex27PropertyFilterRegistered',
				metadata: window.diviApex27PropertyFilterMetadata || null,
				loadingText: 'Loading Apex27 property results...',
				createRenderer: createPropertyFilterPreviewRenderer
			},
			{
				flag: 'diviApex27PropertySearchFormRegistered',
				metadata: window.diviApex27PropertySearchFormMetadata || null,
				loadingText: 'Loading Apex27 property search form...',
				createRenderer: createSearchFormPreviewRenderer
			}
		];

		let anyRegistered = false;
		definitions.forEach((definition) => {
			const registered = registerModule(definition);
			anyRegistered = anyRegistered || registered;
		});

		return anyRegistered;
	}

	if (hooks) {
		hooks.addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'divi-apex27/property-filter', registerModules);
	}

	setTimeout(registerModules, 1500);
})();
