import axios from 'axios';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useTableIndexStore = defineStore('TableIndexStore', () => {
    const tableIndex = ref(0);

    const incrementIndex = () => {
        tableIndex.value = tableIndex.value + 1;
    };

    return { tableIndex, incrementIndex };
});