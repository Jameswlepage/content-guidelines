/**
 * Internal dependencies
 */
import {
	SET_GUIDELINES,
	SET_DRAFT,
	SET_SAVING,
	SET_PUBLISHING,
	SET_ERROR,
	SET_REVISIONS,
	SET_RESTORING,
	SET_TEST_RESULTS,
	SET_RUNNING_TEST,
} from './actions';

/**
 * Default state.
 */
const DEFAULT_STATE = {
	active: null,
	draft: null,
	postId: null,
	updatedAt: null,
	revisionCount: 0,
	isSaving: false,
	isPublishing: false,
	error: null,
	revisions: [],
	isRestoring: false,
	testResults: null,
	isRunningTest: false,
};

/**
 * Reducer.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Action object.
 * @return {Object} New state.
 */
export default function reducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case SET_GUIDELINES:
			return {
				...state,
				active: action.payload.active,
				draft: action.payload.draft || state.draft,
				postId: action.payload.post_id,
				updatedAt: action.payload.updated_at,
				revisionCount: action.payload.revision_count,
			};

		case SET_DRAFT:
			return {
				...state,
				draft: action.payload,
			};

		case SET_SAVING:
			return {
				...state,
				isSaving: action.payload,
			};

		case SET_PUBLISHING:
			return {
				...state,
				isPublishing: action.payload,
			};

		case SET_ERROR:
			return {
				...state,
				error: action.payload,
			};

		case SET_REVISIONS:
			return {
				...state,
				revisions: action.payload,
			};

		case SET_RESTORING:
			return {
				...state,
				isRestoring: action.payload,
			};

		case SET_TEST_RESULTS:
			return {
				...state,
				testResults: action.payload,
			};

		case SET_RUNNING_TEST:
			return {
				...state,
				isRunningTest: action.payload,
			};

		default:
			return state;
	}
}
