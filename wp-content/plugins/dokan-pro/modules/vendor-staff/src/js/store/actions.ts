import apiFetch from '@wordpress/api-fetch';

const actions = {
    setLoading( loading ) {
        return {
            type: 'SET_LOADING',
            payload: loading,
        };
    },
    async fetchAllCapabilities() {
        const response = await apiFetch( {
            path: 'dokan/v1/vendor-staff/capabilities',
            method: 'GET',
        } );
        return {
            type: 'SET_ALL_CAPABILITIES',
            payload: response,
        };
    },

    updateEditCapabilities( capabilities ) {
        return {
            type: 'SET_EDIT_CAPABILITIES',
            payload: capabilities,
        };
    },

    async getCapabilities( id ) {
        try {
            // set loading to true
            const response = await apiFetch( {
                path: `dokan/v1/vendor-staff/${ id }/capabilities`,
                method: 'GET',
            } );
            return {
                type: 'SET_EDIT_CAPABILITIES',
                payload: response,
            };
        } catch ( error ) {
            return {
                type: 'SET_ERROR',
                payload: {
                    message: error.message,
                    code: error.code,
                },
            };
        }
    },
};

export default actions;
