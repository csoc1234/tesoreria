//Vuex
import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex);

const store = new Vuex.Store({
    state: {
        config: 'config',

    },
    getters: {
        config: state => state.config
    },
});

export default store