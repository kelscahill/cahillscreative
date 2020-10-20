import loadItems from '../modules/loadItems';

/**
 * Set up the instance
 *
 * @param {object} alm
 * @since 5.3.0
 */

export function elementorInit(alm) {
	if (!alm.addons.elementor || !alm.addons.elementor_type || !alm.addons.elementor_type === 'posts') {
		return false;
	}
	let target = alm.addons.elementor_target_element;
	if (target) {
		// Set button data attributes
		alm.button.dataset.page = alm.addons.elementor_paged;

		// BSet button URL
		let nextPage = alm.addons.elementor_pages[alm.addons.elementor_paged - 1];
		alm.button.dataset.url = nextPage ? nextPage : '';

		// Set a11y attributes
		target.setAttribute('aria-live', 'polite');
		target.setAttribute('aria-atomic', 'true');

		alm.listing.removeAttribute('aria-live');
		alm.listing.removeAttribute('aria-atomic');

		// Set data atts on 1st grid item
		let item = target.querySelector(`.${alm.addons.elementor_item_class}`); // Get first `.product` item
		if (item) {
			item.classList.add('alm-elementor');
			item.dataset.url = window.location;
			item.dataset.page = alm.addons.elementor_paged;
			item.dataset.pageTitle = document.title;
		}

		if (alm.addons.elementor_paged > 1) {
			// maybe soon
			//almElementorResultsTextInit(alm);
		}
	}
}

/**
 * elementor
 * Core ALM Elementor loader
 *
 * @param {HTMLElement} content
 * @param {object} alm
 * @param {String} pageTitle
 * @since 5.3.0
 */

export function elementor(content, alm, pageTitle = document.title) {
	if (!content || !alm) {
		return false;
	}

	return new Promise((resolve) => {
		let container = alm.addons.elementor_target_element.querySelector(`.${alm.addons.elementor_container_class}`); // Get post container
		let items = content.querySelectorAll(`.${alm.addons.elementor_item_class}`); // Get all items in container
		let url = alm.addons.elementor_pages[alm.page - 1];

		if (container && items && url) {
			// Convert NodeList to Array
			items = Array.prototype.slice.call(items);

			// Load the items
			(async function () {
				await loadItems(container, items, alm, pageTitle, url, 'alm-elementor');
				resolve(true);
			})().catch((e) => {
				console.log(e, 'There was an error with Elementor');
			});
		}
	});
}

/**
 * elementorGetContent
 * Get the content, title and results text from the Ajax response
 *
 * @param {object} alm
 * @since 5.4.0
 */

export function elementorGetContent(response, alm) {
	let data = {
		html: '',
		meta: {
			postcount: 1,
			totalposts: alm.localize.total_posts,
			debug: 'Elementor Query',
		},
	};
	if (response.status === 200 && response.data) {
		let div = document.createElement('div');
		div.innerHTML = response.data;

		// Get Page Title
		let title = div.querySelector('title').innerHTML;
		data.pageTitle = title;

		// Get Elementor Items HTML
		let items = div.querySelector(`${alm.addons.elementor_target} .${alm.addons.elementor_container_class}`);
		data.html = items ? items.innerHTML : '';

		// Results Text
		//almElementorResultsText(div, alm);
	}

	return data;
}

/**
 * Return the paging URLs from `.elementor-pagination`
 *
 * @param {*} target
 * @return {NodeList} pages
 */
export function elementorGetPages(pagination_class, pagination_item, target) {
	if (!target) {
		return false;
	}
	let pagination = target.querySelector(`.${pagination_class}`);
	if (!pagination) {
		return 1;
	}
	let pages = pagination.querySelectorAll(pagination_item);
	return pages;
}
