import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useLoadingStore = defineStore('LoadingStore', () => {
    const isLoading = ref(false);

    const toggleLoading = () => {
        isLoading.value = isLoading.value ? false : true;
    };

    return { isLoading, toggleLoading };
});