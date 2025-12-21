/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect, useMemo } from '@wordpress/element';
import {
	Navigator,
	useNavigator,
	Button,
	Notice,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
	__experimentalHeading as Heading,
	__experimentalSpacer as Spacer,
	__experimentalText as Text,
	Flex,
	FlexItem,
	TextareaControl,
	TextControl,
	SelectControl,
	FormTokenField,
} from '@wordpress/components';
import { chevronRight, chevronLeft } from '@wordpress/icons';
import { isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../store';
import RepeaterControl from '../controls/repeater-control';
import './style.scss';

/**
 * Section definitions.
 */
const SECTIONS = [
	{
		id: 'brand_context',
		title: __( 'Brand & site context', 'content-guidelines' ),
		description: __( 'Define what your site is about and who it serves.', 'content-guidelines' ),
	},
	{
		id: 'voice_tone',
		title: __( 'Voice & tone', 'content-guidelines' ),
		description: __( 'Set the personality and emotional feel of your content.', 'content-guidelines' ),
	},
	{
		id: 'copy_rules',
		title: __( 'Copy rules', 'content-guidelines' ),
		description: __( 'Specific dos and don\'ts for writing.', 'content-guidelines' ),
	},
	{
		id: 'vocabulary',
		title: __( 'Vocabulary', 'content-guidelines' ),
		description: __( 'Preferred terms and words to avoid.', 'content-guidelines' ),
	},
	{
		id: 'images',
		title: __( 'Images', 'content-guidelines' ),
		description: __( 'Guidelines for image selection and alt text.', 'content-guidelines' ),
	},
	{
		id: 'notes',
		title: __( 'Additional notes', 'content-guidelines' ),
		description: __( 'Any other guidelines or context.', 'content-guidelines' ),
	},
];

/**
 * Goal options.
 */
const GOAL_OPTIONS = [
	{ value: '', label: __( 'Select a goal...', 'content-guidelines' ) },
	{ value: 'subscribe', label: __( 'Get email subscribers', 'content-guidelines' ) },
	{ value: 'sell', label: __( 'Sell products/services', 'content-guidelines' ) },
	{ value: 'inform', label: __( 'Inform and educate', 'content-guidelines' ) },
	{ value: 'community', label: __( 'Build community', 'content-guidelines' ) },
	{ value: 'other', label: __( 'Other', 'content-guidelines' ) },
];

/**
 * Section card component.
 *
 * @param {Object}   props             Component props.
 * @param {Object}   props.section     Section definition.
 * @param {string}   props.statusText  Status text.
 * @param {Function} props.onClick     Click handler.
 * @return {JSX.Element} Section card.
 */
function SectionCard( { section, statusText, onClick } ) {
	const navigator = useNavigator();

	const handleClick = () => {
		if ( onClick ) {
			onClick();
		}
		navigator.goTo( `/${ section.id }` );
	};

	return (
		<Button
			__next40pxDefaultSize
			className="library-panel__section-card"
			onClick={ handleClick }
		>
			<Flex justify="space-between">
				<FlexItem>
					<VStack spacing={ 1 }>
						<Text className="library-panel__section-title">
							{ section.title }
						</Text>
						<Text className="library-panel__section-description" variant="muted">
							{ section.description }
						</Text>
					</VStack>
				</FlexItem>
				<Flex justify="flex-end" gap={ 2 }>
					{ statusText && (
						<FlexItem>
							<Text className="library-panel__section-status" variant="muted">
								{ statusText }
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
 * Section detail screen component.
 *
 * @param {Object}   props           Component props.
 * @param {Object}   props.section   Section definition.
 * @param {Function} props.onBack    Back handler.
 * @return {JSX.Element} Section detail screen.
 */
function SectionDetailScreen( { section, onBack } ) {
	const draft = useSelect( ( select ) => select( STORE_NAME ).getDraft() || {}, [] );
	const { updateDraftSection, updateDraft } = useDispatch( STORE_NAME );

	const sectionData = draft[ section.id ] || {};

	const handleChange = ( field, value ) => {
		updateDraftSection( section.id, { [ field ]: value } );
	};

	const handleTopLevelChange = ( field, value ) => {
		updateDraft( { [ field ]: value } );
	};

	const renderContent = () => {
		switch ( section.id ) {
			case 'brand_context':
				return (
					<VStack spacing={ 4 }>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'What is this site about?', 'content-guidelines' ) }
							help={ __( 'A brief description of your site, business, or publication.', 'content-guidelines' ) }
							value={ sectionData.site_description || '' }
							onChange={ ( value ) => handleChange( 'site_description', value ) }
							rows={ 3 }
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Target audience', 'content-guidelines' ) }
							help={ __( 'Who are you writing for?', 'content-guidelines' ) }
							value={ sectionData.audience || '' }
							onChange={ ( value ) => handleChange( 'audience', value ) }
						/>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Primary goal', 'content-guidelines' ) }
							help={ __( 'What do you want visitors to do?', 'content-guidelines' ) }
							value={ sectionData.primary_goal || '' }
							options={ GOAL_OPTIONS }
							onChange={ ( value ) => handleChange( 'primary_goal', value ) }
						/>
						<FormTokenField
							label={ __( 'Topics / coverage areas', 'content-guidelines' ) }
							value={ sectionData.topics || [] }
							onChange={ ( value ) => handleChange( 'topics', value ) }
							__experimentalExpandOnFocus
							__experimentalShowHowTo={ false }
						/>
					</VStack>
				);

			case 'voice_tone':
				return (
					<VStack spacing={ 4 }>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Voice description', 'content-guidelines' ) }
							help={ __( 'How should your brand\'s personality come across?', 'content-guidelines' ) }
							value={ sectionData.description || '' }
							onChange={ ( value ) => handleChange( 'description', value ) }
							rows={ 3 }
						/>
						<FormTokenField
							label={ __( 'Voice attributes', 'content-guidelines' ) }
							value={ sectionData.attributes || [] }
							onChange={ ( value ) => handleChange( 'attributes', value ) }
							__experimentalExpandOnFocus
							__experimentalShowHowTo={ false }
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Tone notes', 'content-guidelines' ) }
							help={ __( 'Any additional guidance on tone adjustments.', 'content-guidelines' ) }
							value={ sectionData.tone_notes || '' }
							onChange={ ( value ) => handleChange( 'tone_notes', value ) }
							rows={ 2 }
						/>
					</VStack>
				);

			case 'copy_rules':
				return (
					<VStack spacing={ 4 }>
						<RepeaterControl
							label={ __( 'Do', 'content-guidelines' ) }
							items={ sectionData.dos || [] }
							onChange={ ( value ) => handleChange( 'dos', value ) }
							placeholder={ __( 'Add a rule...', 'content-guidelines' ) }
						/>
						<RepeaterControl
							label={ __( "Don't", 'content-guidelines' ) }
							items={ sectionData.donts || [] }
							onChange={ ( value ) => handleChange( 'donts', value ) }
							placeholder={ __( 'Add a rule...', 'content-guidelines' ) }
						/>
					</VStack>
				);

			case 'vocabulary':
				return (
					<VStack spacing={ 4 }>
						<FormTokenField
							label={ __( 'Preferred terms', 'content-guidelines' ) }
							value={ sectionData.preferred || [] }
							onChange={ ( value ) => handleChange( 'preferred', value ) }
							__experimentalExpandOnFocus
							__experimentalShowHowTo={ false }
						/>
						<FormTokenField
							label={ __( 'Terms to avoid', 'content-guidelines' ) }
							value={ sectionData.avoid || [] }
							onChange={ ( value ) => handleChange( 'avoid', value ) }
							__experimentalExpandOnFocus
							__experimentalShowHowTo={ false }
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Vocabulary notes', 'content-guidelines' ) }
							value={ sectionData.notes || '' }
							onChange={ ( value ) => handleChange( 'notes', value ) }
							rows={ 2 }
						/>
					</VStack>
				);

			case 'images':
				return (
					<VStack spacing={ 4 }>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Image style', 'content-guidelines' ) }
							help={ __( 'Describe the visual style for images.', 'content-guidelines' ) }
							value={ sectionData.style || '' }
							onChange={ ( value ) => handleChange( 'style', value ) }
							rows={ 2 }
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Alt text guidelines', 'content-guidelines' ) }
							help={ __( 'How should alt text be written?', 'content-guidelines' ) }
							value={ sectionData.alt_text_guidelines || '' }
							onChange={ ( value ) => handleChange( 'alt_text_guidelines', value ) }
							rows={ 2 }
						/>
					</VStack>
				);

			case 'notes':
				return (
					<VStack spacing={ 4 }>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Additional notes', 'content-guidelines' ) }
							help={ __( 'Any other guidelines or context that doesn\'t fit elsewhere.', 'content-guidelines' ) }
							value={ draft.notes || '' }
							onChange={ ( value ) => handleTopLevelChange( 'notes', value ) }
							rows={ 5 }
						/>
					</VStack>
				);

			default:
				return null;
		}
	};

	return (
		<div className="library-panel__detail-container">
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
					className="library-panel__detail-title"
				>
					{ section.title }
				</Heading>
			</Flex>

			<Spacer margin={ 4 } />

			{ renderContent() }
		</div>
	);
}

/**
 * Get status text for a section.
 *
 * @param {string} sectionId Section ID.
 * @param {Object} draft     Draft data.
 * @return {string|null} Status text.
 */
function getSectionStatus( sectionId, draft ) {
	const sectionData = draft[ sectionId ];
	if ( ! sectionData ) {
		return null;
	}

	// Check if section has any data
	const hasData = Object.values( sectionData ).some( ( value ) => {
		if ( Array.isArray( value ) ) {
			return value.length > 0;
		}
		return value && value.length > 0;
	} );

	return hasData ? __( 'Configured', 'content-guidelines' ) : null;
}

/**
 * Library panel with Navigator drill-down.
 *
 * @return {JSX.Element} Library panel.
 */
export default function LibraryPanel() {
	const [ selectedSection, setSelectedSection ] = useState( null );

	const { hasDraft, draftHasChanges, draft } = useSelect( ( select ) => {
		return {
			hasDraft: select( STORE_NAME ).hasDraft(),
			draftHasChanges: select( STORE_NAME ).draftHasChanges(),
			draft: select( STORE_NAME ).getDraft() || {},
		};
	}, [] );

	// URL synchronization
	useEffect( () => {
		const params = new URLSearchParams( window.location.search );
		const section = params.get( 'section' );
		if ( section ) {
			const found = SECTIONS.find( ( s ) => s.id === section );
			if ( found ) {
				setSelectedSection( found );
			}
		}
	}, [] );

	const updateURL = ( section ) => {
		const url = new URL( window.location.href );
		if ( section ) {
			url.searchParams.set( 'section', section.id );
		} else {
			url.searchParams.delete( 'section' );
		}
		window.history.replaceState( {}, '', url );
	};

	const handleSectionClick = ( section ) => {
		setSelectedSection( section );
		updateURL( section );
	};

	const handleBack = () => {
		setSelectedSection( null );
		updateURL( null );
	};

	return (
		<div className="library-panel">
			{ hasDraft && draftHasChanges && (
				<Notice status="warning" isDismissible={ false } className="library-panel__notice">
					{ __( 'Draft changes not published.', 'content-guidelines' ) }
				</Notice>
			) }

			<Navigator initialPath={ selectedSection ? `/${ selectedSection.id }` : '/' }>
				<Navigator.Screen path="/">
					<VStack spacing={ 0 }>
						<ul role="list" className="library-panel__list">
							{ SECTIONS.map( ( section ) => (
								<li key={ section.id } className="library-panel__list-item">
									<SectionCard
										section={ section }
										statusText={ getSectionStatus( section.id, draft ) }
										onClick={ () => handleSectionClick( section ) }
									/>
								</li>
							) ) }
						</ul>
					</VStack>
				</Navigator.Screen>

				{ SECTIONS.map( ( section ) => (
					<Navigator.Screen key={ section.id } path={ `/${ section.id }` }>
						<SectionDetailScreen
							section={ section }
							onBack={ handleBack }
						/>
					</Navigator.Screen>
				) ) }
			</Navigator>
		</div>
	);
}
