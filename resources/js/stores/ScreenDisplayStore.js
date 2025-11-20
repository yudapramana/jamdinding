import { defineStore } from 'pinia';
import { ref } from 'vue';


export const useScreenDisplayStore = defineStore('ScreenDisplayStore', () => {
    const isMobile = ref(false);
    const sWidth = ref(0);
    const sHeight = ref(0);

    const toggleIsMobile = () => {
        getScreenSize();
        
        if (sWidth.value <= 760) {
            isMobile.value = true;
        } else {
            isMobile.value = false
        }
    };

    const getScreenSize = () => {
        sWidth.value = document.documentElement.clientWidth;
        sHeight.value = document.documentElement.clientHeight;
        
        // console.log(sWidth.value);
        // console.log(sHeight.value);

        // console.log(`${sWidth.value} <= 760`);
        // console.log(sWidth.value <= 760);

        if (sWidth.value <= 760) {
            isMobile.value = true;
        } else {
            isMobile.value = false
        }

        // console.log('getScreenSize');
        // console.log(isMobile.value);
       
    }

    return { isMobile, sWidth, sHeight, toggleIsMobile, getScreenSize };
});