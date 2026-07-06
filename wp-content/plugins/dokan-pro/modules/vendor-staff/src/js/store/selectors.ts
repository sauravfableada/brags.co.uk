// Selectors
const selectors = {
    getAllCapabilities( state ) {
        return state.allCapabilities;
    },
    isLoading( state ) {
        return state.loading;
    },
    getEditCapabilities( state ) {
        return state.editCapabilities;
    },
    getErrorCode( state ) {
        return state.errorCode;
    },
};

export default selectors;
