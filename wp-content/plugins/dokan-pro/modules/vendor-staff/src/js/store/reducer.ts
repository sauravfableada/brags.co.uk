const initialState = {
    allCapabilities: {
        all: {},
        default: [],
    },
    editCapabilities: {},
    error: null,
    errorCode: null,
    loading: true,
};

// Reducer
const reducer = ( state = initialState, action ) => {
    switch ( action.type ) {
        case 'SET_LOADING':
            return {
                ...state,
                loading: action.payload,
            };
        case 'SET_ALL_CAPABILITIES':
            return {
                ...state,
                allCapabilities: action.payload,
            };
        case 'SET_EDIT_CAPABILITIES':
            return {
                ...state,
                editCapabilities: action.payload,
                error: null,
                errorCode: null,
                loading: false,
            };
        case 'SET_ERROR':
            return {
                ...state,
                editCapabilities: {},
                error: action.payload.message,
                errorCode: action.payload.code,
                loading: false,
            };
        default:
            return state;
    }
};

export default reducer;
