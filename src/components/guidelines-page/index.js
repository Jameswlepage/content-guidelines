/**
 * WordPress dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import {
	TabPanel,
	Spinner,
	Notice,
	Button,
} from '@wordpress/components';
import { backup } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../store';
import LibraryPanel from '../library-panel';
import BlocksPanel from '../blocks-panel';
import PlaygroundPanel from '../playground';
import HistoryPanel from '../history';
import EmptyState from '../guidelines-screen/empty-state';
import './style.scss';

/**
 * Header actions component.
 *
 * @param {Object}   props               Component props.
 * @param {boolean}  props.hasDraft      Whether there are draft changes.
 * @param {boolean}  props.isSaving      Whether currently saving.
 * @param {boolean}  props.isPublishing  Whether currently publishing.
 * @param {Function} props.onShowHistory Callback to show history.
 * @return {JSX.Element} Header actions.
 */
function HeaderActions( { hasDraft, isSaving, isPublishing, onShowHistory } ) {
	const { saveDraft, publishGuidelines, discardDraft } =
		useDispatch( STORE_NAME );

	return (
		<div className="guidelines-page__header-actions">
			<Button
				icon={ backup }
				label={ __( 'History', 'content-guidelines' ) }
				onClick={ onShowHistory }
			/>
			{ hasDraft && (
				<>
					<Button
						variant="tertiary"
						onClick={ discardDraft }
						disabled={ isSaving || isPublishing }
					>
						{ __( 'Discard', 'content-guidelines' ) }
					</Button>
					<Button
						variant="secondary"
						onClick={ saveDraft }
						isBusy={ isSaving }
						disabled={ isSaving || isPublishing }
					>
						{ __( 'Save draft', 'content-guidelines' ) }
					</Button>
				</>
			) }
			<Button
				variant="primary"
				onClick={ publishGuidelines }
				isBusy={ isPublishing }
				disabled={ isSaving || isPublishing || ! hasDraft }
			>
				{ __( 'Publish', 'content-guidelines' ) }
			</Button>
		</div>
	);
}

/**
 * Main guidelines page component using @wordpress/admin-ui Page wrapper.
 *
 * @return {JSX.Element} The guidelines page.
 */
export default function GuidelinesPage() {
	const [ showHistory, setShowHistory ] = useState( false );

	const {
		active,
		draft,
		hasDraft,
		hasGuidelines,
		isSaving,
		isPublishing,
		error,
	} = useSelect( ( select ) => {
		const store = select( STORE_NAME );
		return {
			active: store.getActive(),
			draft: store.getDraft(),
			hasDraft: store.hasDraft(),
			hasGuidelines: store.hasGuidelines(),
			isSaving: store.isSaving(),
			isPublishing: store.isPublishing(),
			error: store.getError(),
		};
	}, [] );

	const { initializeEditor, setError: clearError } = useDispatch( STORE_NAME );

	// Initialize editor when guidelines load.
	useEffect( () => {
		if ( active && ! draft ) {
			initializeEditor();
		}
	}, [ active, draft, initializeEditor ] );

	// Loading state.
	const isLoading = active === undefined;

	if ( isLoading ) {
		return (
			<Page title={ __( 'Guidelines', 'content-guidelines' ) }>
				<div className="guidelines-page__loading">
					<Spinner />
					<p>{ __( 'Loading guidelines...', 'content-guidelines' ) }</p>
				</div>
			</Page>
		);
	}

	// Empty state - no guidelines yet.
	if ( ! hasGuidelines && ! hasDraft ) {
		return (
			<Page title={ __( 'Guidelines', 'content-guidelines' ) }>
				<EmptyState />
			</Page>
		);
	}

	const tabs = [
		{
			name: 'library',
			title: __( 'Library', 'content-guidelines' ),
		},
		{
			name: 'blocks',
			title: __( 'Blocks', 'content-guidelines' ),
		},
		{
			name: 'playground',
			title: __( 'Playground', 'content-guidelines' ),
		},
	];

	return (
		<Page
			title={ __( 'Guidelines', 'content-guidelines' ) }
			actions={
				<HeaderActions
					hasDraft={ hasDraft }
					isSaving={ isSaving }
					isPublishing={ isPublishing }
					onShowHistory={ () => setShowHistory( true ) }
				/>
			}
		>
			{ error && (
				<Notice
					status="error"
					isDismissible
					onDismiss={ () => clearError( null ) }
					className="guidelines-page__error"
				>
					{ error }
				</Notice>
			) }

			<TabPanel
				className="guidelines-page__tabs"
				activeClass="is-active"
				tabs={ tabs }
			>
				{ ( tab ) => (
					<div className="guidelines-page__tab-panel">
						{ tab.name === 'library' && <LibraryPanel /> }
						{ tab.name === 'blocks' && <BlocksPanel /> }
						{ tab.name === 'playground' && <PlaygroundPanel /> }
					</div>
				) }
			</TabPanel>

			{ showHistory && (
				<HistoryPanel onClose={ () => setShowHistory( false ) } />
			) }
		</Page>
	);
}
