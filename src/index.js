/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './store';
import GuidelinesPage from './components/guidelines-page';
import './style.scss';

/**
 * Initialize the Content Guidelines admin page.
 */
function initContentGuidelines() {
	const config = window.contentGuidelinesConfig;

	if ( ! config || ! config.mountId ) {
		// eslint-disable-next-line no-console
		console.error( 'Content Guidelines: Missing configuration' );
		return;
	}

	const container = document.getElementById( config.mountId );

	if ( ! container ) {
		// eslint-disable-next-line no-console
		console.error(
			`Content Guidelines: Mount element #${ config.mountId } not found`
		);
		return;
	}

	const root = createRoot( container );
	root.render( <GuidelinesPage /> );
}

// Initialize when DOM is ready.
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initContentGuidelines );
} else {
	initContentGuidelines();
}

/**
 * Export components for potential external use.
 */
export { default as GuidelinesPage } from './components/guidelines-page';
export { STORE_NAME } from './store';
