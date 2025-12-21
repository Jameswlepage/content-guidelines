/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useMemo, useEffect } from '@wordpress/element';
import {
	Button,
	SearchControl,
	Navigator,
	useNavigator,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
	__experimentalText as Text,
	__experimentalHeading as Heading,
	__experimentalSpacer as Spacer,
	Flex,
	FlexItem,
	TextareaControl,
	Spinner,
} from '@wordpress/components';
import { store as blocksStore } from '@wordpress/blocks';
import { store as coreStore } from '@wordpress/core-data';
import { chevronRight, chevronLeft } from '@wordpress/icons';
import { isRTL } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../store';
import RepeaterControl from '../controls/repeater-control';
import './style.scss';

/**
 * Common block types that benefit from guidelines.
 */
const PRIORITY_BLOCKS = [
	'core/paragraph',
	'core/heading',
	'core/button',
	'core/image',
	'core/list',
	'core/quote',
];

/**
 * Block card component.
 *
 * @param {Object}   props              Component props.
 * @param {Object}   props.block        Block type object.
 * @param {string}   props.variantsText Status text.
 * @param {Function} props.onClick      Click handler.
 * @param {string}   props.navigatorPath Navigator path.
 * @return {JSX.Element} Block card.
 */
function BlockCard( { block, variantsText, onClick, navigatorPath } ) {
	const navigator = useNavigator();
	const { title, icon } = block;

	const handleClick = () => {
		if ( onClick ) {
			onClick();
		}
		if ( navigatorPath ) {
			navigator.goTo( navigatorPath );
		}
	};

	return (
		<Button
			__next40pxDefaultSize
			className="blocks-panel__block-card"
			onClick={ handleClick }
		>
			<Flex justify="space-between">
				<FlexItem>
					<Text className="blocks-panel__block-title">
						{ title }
					</Text>
				</FlexItem>
				<Flex justify="flex-end" gap={ 2 }>
					{ variantsText && (
						<FlexItem>
							<Text className="blocks-panel__block-status" variant="muted">
								{ variantsText }
							</Text>
						</FlexItem>
					) }
					<FlexItem>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							width="24"
							height="24"
							aria-hidden="true"
							focusable="false"
						>
							<path d="M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z" />
						</svg>
					</FlexItem>
				</Flex>
			</Flex>
		</Button>
	);
}

/**
 * Block detail screen component.
 *
 * @param {Object} props           Component props.
 * @param {Object} props.block     Selected block.
 * @param {Function} props.onBack  Back handler.
 * @return {JSX.Element} Block detail screen.
 */
function BlockDetailScreen( { block, onBack } ) {
	const { blockGuidelines } = useSelect( ( select ) => {
		const draft = select( STORE_NAME ).getDraft();
		return {
			blockGuidelines: draft?.blocks?.[ block?.name ] || {},
		};
	}, [ block?.name ] );

	const { updateBlockGuidelines } = useDispatch( STORE_NAME );

	const updateNestedField = ( parent, field, value ) => {
		updateBlockGuidelines( block.name, {
			...blockGuidelines,
			[ parent ]: {
				...( blockGuidelines[ parent ] || {} ),
				[ field ]: value,
			},
		} );
	};

	const updateField = ( field, value ) => {
		updateBlockGuidelines( block.name, {
			...blockGuidelines,
			[ field ]: value,
		} );
	};

	if ( ! block ) {
		return null;
	}

	return (
		<div className="blocks-panel__detail-container">
			<Flex justify="flex-start">
				<Navigator.BackButton
					icon={ isRTL() ? chevronRight : chevronLeft }
					size="small"
					onClick={ onBack }
					label={ __( 'Back', 'content-guidelines' ) }
				/>
				<Heading
					level={ 2 }
					size={ 13 }
					className="blocks-panel__detail-title"
				>
					{ block.title }
				</Heading>
			</Flex>

			<Spacer margin={ 4 } />

			<Text>
				{ __( 'Set AI guidelines specific to this block type.', 'content-guidelines' ) }
			</Text>

			<Spacer margin={ 4 } />

			<VStack spacing={ 6 }>
				{ /* Copy Rules */ }
				<VStack spacing={ 3 }>
					<Heading level={ 3 } className="blocks-panel__section-title">
						{ __( 'Copy Rules', 'content-guidelines' ) }
					</Heading>
					<RepeaterControl
						label={ __( 'Do', 'content-guidelines' ) }
						items={ blockGuidelines.copy_rules?.dos || [] }
						onChange={ ( value ) => updateNestedField( 'copy_rules', 'dos', value ) }
						placeholder={ __( 'Add a rule…', 'content-guidelines' ) }
					/>
					<RepeaterControl
						label={ __( "Don't", 'content-guidelines' ) }
						items={ blockGuidelines.copy_rules?.donts || [] }
						onChange={ ( value ) => updateNestedField( 'copy_rules', 'donts', value ) }
						placeholder={ __( 'Add a rule…', 'content-guidelines' ) }
					/>
				</VStack>

				{ /* Notes */ }
				<VStack spacing={ 3 }>
					<Heading level={ 3 } className="blocks-panel__section-title">
						{ __( 'Additional Notes', 'content-guidelines' ) }
					</Heading>
					<TextareaControl
						__nextHasNoMarginBottom
						value={ blockGuidelines.notes || '' }
						onChange={ ( value ) => updateField( 'notes', value ) }
						placeholder={ __( 'Any other guidelines for this block…', 'content-guidelines' ) }
						rows={ 3 }
					/>
				</VStack>
			</VStack>
		</div>
	);
}

/**
 * Blocks panel component with Navigator drill-down.
 *
 * @return {JSX.Element} Blocks panel.
 */
export default function BlocksPanel() {
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ selectedBlock, setSelectedBlock ] = useState( null );
	const [ blockTypes, setBlockTypes ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	// Fetch block types from REST API on mount.
	// This works on any admin page, unlike the blocks store which
	// only has blocks when the block editor is loaded.
	useEffect( () => {
		let isMounted = true;

		async function fetchBlockTypes() {
			try {
				setIsLoading( true );
				setError( null );

				// Fetch all block types from the REST API.
				const response = await apiFetch( {
					path: '/wp/v2/block-types?per_page=100',
				} );

				if ( isMounted ) {
					// Transform REST API response to match blocks store format.
					const blocks = response.map( ( block ) => ( {
						name: block.name,
						title: block.title || block.name,
						description: block.description || '',
						category: block.category || 'common',
						icon: block.icon || null,
						keywords: block.keywords || [],
					} ) );

					setBlockTypes( blocks );
					setIsLoading( false );
				}
			} catch ( err ) {
				if ( isMounted ) {
					setError( err.message || __( 'Failed to load blocks.', 'content-guidelines' ) );
					setIsLoading( false );
				}
			}
		}

		fetchBlockTypes();

		return () => {
			isMounted = false;
		};
	}, [] );

	const { blockGuidelines } = useSelect( ( select ) => {
		const draft = select( STORE_NAME ).getDraft();
		return {
			blockGuidelines: draft?.blocks || {},
		};
	}, [] );

	// Filter and sort blocks
	const filteredBlocks = useMemo( () => {
		let blocks = blockTypes.filter( ( block ) => {
			if ( block.name.startsWith( 'core/legacy-' ) ) {
				return false;
			}
			if ( searchTerm ) {
				const search = searchTerm.toLowerCase();
				return (
					block.title.toLowerCase().includes( search ) ||
					block.name.toLowerCase().includes( search )
				);
			}
			return true;
		} );

		blocks.sort( ( a, b ) => {
			const aPriority = PRIORITY_BLOCKS.indexOf( a.name );
			const bPriority = PRIORITY_BLOCKS.indexOf( b.name );
			if ( aPriority !== -1 && bPriority !== -1 ) return aPriority - bPriority;
			if ( aPriority !== -1 ) return -1;
			if ( bPriority !== -1 ) return 1;
			return a.title.localeCompare( b.title );
		} );

		return blocks;
	}, [ blockTypes, searchTerm ] );

	const { priorityBlocks, otherBlocks } = useMemo( () => {
		const priority = [];
		const other = [];
		filteredBlocks.forEach( ( block ) => {
			if ( PRIORITY_BLOCKS.includes( block.name ) ) {
				priority.push( block );
			} else {
				other.push( block );
			}
		} );
		return { priorityBlocks: priority, otherBlocks: other };
	}, [ filteredBlocks ] );

	/**
	 * Check if a block has meaningful guidelines configured.
	 *
	 * @param {string} blockName Block name.
	 * @return {string|null} Status text or null.
	 */
	const getBlockStatus = ( blockName ) => {
		const guidelines = blockGuidelines[ blockName ];
		if ( ! guidelines ) {
			return null;
		}

		// Check if there's any actual content
		const hasCopyRules =
			( guidelines.copy_rules?.dos?.length > 0 ) ||
			( guidelines.copy_rules?.donts?.length > 0 );
		const hasNotes = guidelines.notes && guidelines.notes.trim().length > 0;

		if ( hasCopyRules || hasNotes ) {
			return __( 'Configured', 'content-guidelines' );
		}

		return null;
	};

	// Show loading state.
	if ( isLoading ) {
		return (
			<div className="blocks-panel">
				<div className="blocks-panel__loading">
					<Spinner />
					<Text variant="muted">
						{ __( 'Loading blocks…', 'content-guidelines' ) }
					</Text>
				</div>
			</div>
		);
	}

	// Show error state.
	if ( error ) {
		return (
			<div className="blocks-panel">
				<div className="blocks-panel__loading">
					<Text className="blocks-panel__error">
						{ error }
					</Text>
					<Button
						variant="secondary"
						onClick={ () => window.location.reload() }
					>
						{ __( 'Retry', 'content-guidelines' ) }
					</Button>
				</div>
			</div>
		);
	}

	return (
		<div className="blocks-panel">
			<Navigator initialPath={ selectedBlock ? '/block' : '/' }>
				<Navigator.Screen path="/">
					<div className="blocks-panel__content">
						<div className="blocks-panel__search">
							<SearchControl
								__nextHasNoMarginBottom
								value={ searchTerm }
								onChange={ setSearchTerm }
								placeholder={ __( 'Search blocks…', 'content-guidelines' ) }
							/>
						</div>

						{ priorityBlocks.length > 0 && (
							<div className="blocks-panel__section">
								<h2 className="blocks-panel__list-title">
									{ __( 'Common', 'content-guidelines' ) }
								</h2>
								<ul role="list" className="blocks-panel__list">
									{ priorityBlocks.map( ( block ) => (
										<li key={ block.name } className="blocks-panel__list-item">
											<BlockCard
												block={ block }
												variantsText={ getBlockStatus( block.name ) }
												navigatorPath="/block"
												onClick={ () => setSelectedBlock( block ) }
											/>
										</li>
									) ) }
								</ul>
							</div>
						) }

						{ otherBlocks.length > 0 && (
							<div className="blocks-panel__section">
								<h2 className="blocks-panel__list-title">
									{ __( 'All Blocks', 'content-guidelines' ) }
								</h2>
								<ul role="list" className="blocks-panel__list">
									{ otherBlocks.map( ( block ) => (
										<li key={ block.name } className="blocks-panel__list-item">
											<BlockCard
												block={ block }
												variantsText={ getBlockStatus( block.name ) }
												navigatorPath="/block"
												onClick={ () => setSelectedBlock( block ) }
											/>
										</li>
									) ) }
								</ul>
							</div>
						) }

						{ filteredBlocks.length === 0 && (
							<Text variant="muted" className="blocks-panel__empty">
								{ __( 'No blocks found.', 'content-guidelines' ) }
							</Text>
						) }
					</div>
				</Navigator.Screen>

				<Navigator.Screen path="/block">
					<BlockDetailScreen
						block={ selectedBlock }
						onBack={ () => setSelectedBlock( null ) }
					/>
				</Navigator.Screen>
			</Navigator>
		</div>
	);
}
