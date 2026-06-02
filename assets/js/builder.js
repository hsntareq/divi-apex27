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
		const candidates = [
			currentDivi?.module?.ServerRenderedModule,
			currentDivi?.moduleLibrary?.ServerRenderedModule,
			findComponent(currentDivi?.module, 'ServerRenderedModule'),
			findComponent(currentDivi?.moduleLibrary, 'ServerRenderedModule')
		];

		for (const candidate of candidates) {
			if (typeof candidate === 'function') {
				return candidate;
			}
		}

		return null;
	}

	function getSettingValue(settings, key, fallbackValue) {
		return settings?.[key]?.desktop?.value ?? fallbackValue;
	}

	function getAjaxUrl() {
		return window.et_fb_options?.ajaxurl || window.ajaxurl || '/wp-admin/admin-ajax.php';
	}

	function createForSaleMetadataFallback() {
		const source = window.diviApex27PropertyFilterMetadata;
		if (!source || typeof source !== 'object') {
			return null;
		}

		const metadata = JSON.parse(JSON.stringify(source));
		metadata.name = 'divi-apex27/property-for-sale';
		metadata.title = 'Apex27 Available For Sale';
		metadata.titles = 'Apex27 Available For Sale';
		metadata.moduleClassName = 'divi-apex27-property-for-sale';
		metadata.moduleOrderClassName = 'divi_apex27_property_for_sale';

		const content = metadata?.attributes?.apex27?.default?.content;
		if (content) {
			const allowedDefaultKeys = new Set(['title', 'row_count', 'column_count', 'show_pagination', 'empty_text', 'listing_type', 'type', 'include_sstc']);
			Object.keys(content).forEach((key) => {
				if (!allowedDefaultKeys.has(key)) {
					delete content[key];
				}
			});

			if (content.type?.desktop) {
				content.type.desktop.value = 'sale';
			}

			if (content.listing_type?.desktop) {
				content.listing_type.desktop.value = 'listings';
			}

			content.include_sstc = {
				desktop: {
					value: '0'
				}
			};
		}

		const moduleContentSettings = metadata?.attributes?.apex27?.settings?.content;
		if (moduleContentSettings) {
			Object.keys(moduleContentSettings).forEach((key) => {
				if (!['title', 'column_count', 'row_count', 'show_pagination', 'empty_text'].includes(key)) {
					delete moduleContentSettings[key];
				}
			});
		}

		return metadata;
	}

	function createPropertyFilterPreviewRenderer(React, options = {}) {
		const action = options.action || 'divi_apex27_builder_filter_preview';
		const loadingText = options.loadingText || 'Loading Apex27 property results...';
		const updatingText = options.updatingText || 'Updating results...';

		class PropertyFilterPreview extends React.Component {
			constructor(props) {
				super(props);
				this.state = { html: '', loading: true, error: '' };
				this.lastAttrsKey = '';
				this.isMountedFlag = false;
			}

			componentDidMount() {
				this.isMountedFlag = true;
				this.loadPreview();
			}

			componentDidUpdate(prevProps) {
				if (prevProps.attrsKey !== this.props.attrsKey) {
					this.loadPreview();
				}
			}

			componentWillUnmount() {
				this.isMountedFlag = false;
			}

			loadPreview() {
				const attrs = this.props.attrs || {};
				const attrsKey = this.props.attrsKey || '{}';
				this.lastAttrsKey = attrsKey;
				this.setState({ loading: true, error: '' });

				const body = new URLSearchParams();
				body.set('action', action);
				body.set('attrs', JSON.stringify(attrs));

				fetch(getAjaxUrl(), {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: body.toString()
				})
					.then((response) => response.json())
					.then((data) => {
						if (!this.isMountedFlag || this.lastAttrsKey !== attrsKey) {
							return;
						}

						if (data?.success && typeof data?.data?.html === 'string') {
							this.setState({ html: data.data.html, loading: false, error: '' });
							return;
						}

						this.setState({ loading: false, error: 'Preview unavailable. Save and reload builder.' });
					})
					.catch(() => {
						if (!this.isMountedFlag || this.lastAttrsKey !== attrsKey) {
							return;
						}

						this.setState({ loading: false, error: 'Preview request failed. Save and reload builder.' });
					});
			}

			render() {
				if (this.state.loading && !this.state.html) {
					return React.createElement('div', { className: 'divi-apex27-builder-placeholder' }, loadingText);
				}

				if (this.state.error && !this.state.html) {
					return React.createElement('div', { className: 'divi-apex27-notice divi-apex27-builder-placeholder' }, this.state.error);
				}

				return React.createElement(
					'div',
					{ className: 'divi-apex27-builder-preview-wrap' },
					React.createElement('div', {
						className: 'divi-apex27-builder-mode',
						dangerouslySetInnerHTML: { __html: this.state.html }
					}),
					this.state.loading
						? React.createElement(
							'div',
							{ className: 'divi-apex27-builder-loading-overlay' },
							React.createElement('span', { className: 'divi-apex27-builder-loading-text' }, updatingText)
						)
						: null
				);
			}
		}

		return function edit(props) {
			const attrs = props?.attrs || {};
			const attrsKey = JSON.stringify(attrs);

			return React.createElement(PropertyFilterPreview, { attrs, attrsKey });
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
		const forSaleMetadata = window.diviApex27PropertyForSaleMetadata || createForSaleMetadataFallback();

		const definitions = [
			{
				flag: 'diviApex27PropertyFilterRegistered',
				metadata: window.diviApex27PropertyFilterMetadata || null,
				loadingText: 'Loading Apex27 property results...',
				createRenderer: (React) => createPropertyFilterPreviewRenderer(React, {
					action: 'divi_apex27_builder_filter_preview',
					loadingText: 'Loading Apex27 property results...',
					updatingText: 'Updating results...'
				})
			},
			{
				flag: 'diviApex27PropertySearchFormRegistered',
				metadata: window.diviApex27PropertySearchFormMetadata || null,
				loadingText: 'Loading Apex27 property search form...',
				createRenderer: createSearchFormPreviewRenderer
			},
			{
				flag: 'diviApex27PropertyForSaleRegistered',
				metadata: forSaleMetadata,
				loadingText: 'Loading Apex27 available for sale properties...',
				createRenderer: (React) => createPropertyFilterPreviewRenderer(React, {
					action: 'divi_apex27_builder_for_sale_preview',
					loadingText: 'Loading Apex27 available for sale properties...',
					updatingText: 'Updating available for sale properties...'
				})
			},
			{
				flag: 'diviApex27PropertyValuationRegistered',
				metadata: window.diviApex27PropertyValuationMetadata || null,
				loadingText: 'Loading Apex27 property valuations...',
				createRenderer: (React) => createPropertyFilterPreviewRenderer(React, {
					action: 'divi_apex27_builder_valuation_preview',
					loadingText: 'Loading Apex27 property valuations...',
					updatingText: 'Updating property valuations...'
				})
		},
		{
			flag: 'diviApex27GoogleReviewsRegistered',
			metadata: window.diviApex27GoogleReviewsMetadata || null,
			loadingText: 'Loading Google Reviews...',
			createRenderer: (React) => createPropertyFilterPreviewRenderer(React, {
				action: 'divi_apex27_builder_google_reviews_preview',
				loadingText: 'Loading Google Reviews...',
				updatingText: 'Updating Google Reviews...'
			})
		return anyRegistered;
	}

	if (hooks) {
		hooks.addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'divi-apex27/property-filter', registerModules);
	}

	setTimeout(registerModules, 1500);
})();
